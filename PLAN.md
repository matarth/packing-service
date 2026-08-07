# Packing Service Implementation Plan

## Goal

Build a small, framework-free PHP application that chooses a box for a set of
products. The application is invoked from the command line with a JSON input.
Its primary selection mechanism is an external box-selection API, with a set
of interchangeable, priority-ordered providers available for failover.

Keep the runtime and architecture proportional to the exercise: plain PHP,
explicit constructor injection, and no Symfony application/kernel or DI
container. Existing Doctrine setup may remain behind an adapter where it is
needed by a local fallback provider.

## Architecture and constraints

### Composition

`run.php` is the composition root. It is the only place that wires concrete
objects with `new`; it must construct the dependency graph and invoke the
facade. Application code must receive dependencies through constructors and
must not instantiate HTTP clients, repositories, clocks, or other replaceable
infrastructure itself.

The public execution flow is:

```text
CLI JSON
  -> input DTOs
  -> facade
  -> cached packing service
  -> ordered failover packing service
  -> provider adapters (remote APIs, then local fallback if configured)
  -> output DTOs
  -> CLI JSON
```

Future HTTP controllers or message consumers must reuse the facade by doing
only transport conversion: deserialize input, call the facade, serialize
output.

### Modules

Use the following module roles. Directory placement may follow the existing
`src/` convention; use namespaces that mirror it.

- `Facade`: the application entry point. It accepts a typed input DTO or
  application request, calls the packing-service interface, and maps the
  outcome to a typed output DTO. It owns no infrastructure decisions.
- `Input`: DTOs that deserialize CLI JSON into validated typed values. They
  expose explicit constructors/factories such as `fromArray()` rather than a
  generic serialization library.
- `Output`: DTOs for the CLI response. They implement `JsonSerializable` so
  that the command can emit JSON with `json_encode()`.
- `Service/PackingService`: one small interface, for example
  `findSmallestBox(PackingRequest $request): PackingResult`. Its interface
  includes the fact that a provider may throw `PackingProviderUnavailable`.
- `Service/PackingService/Remote`: adapters that translate the local packing
  interface to a particular external API's request and response format.
- `Service/PackingService/Local`: a local fallback adapter. Keep Doctrine and
  the `Packaging` entity/repository behind this adapter rather than exposing
  Doctrine to the facade or packing-service interface.
- `Service/PackingService/Failover`: a coordinator implementing the same
  packing-service interface. It receives an ordered list of interchangeable
  providers and tries them in order.
- `Service/PackingService/Cache`: a caching decorator implementing the same
  packing-service interface. It wraps the failover coordinator.
- `Exception`: shared, semantic application exceptions. Do not let
  library-specific HTTP or Doctrine exceptions escape an adapter.

This is deliberately a mix of patterns with distinct responsibilities:
remote/local implementations are adapters; caching is a decorator; ordered
provider selection is a failover coordinator/composite.

### Provider behavior

Providers are interchangeable and are configured in an explicit priority
order in the composition root.

- A provider that returns a selected box succeeds; failover stops.
- A healthy provider that returns "no box fits" succeeds; failover stops and
  does not try a later provider.
- A provider that cannot serve the request (connection failure, timeout, HTTP
  5xx, or malformed/unusable remote response) throws
  `PackingProviderUnavailable`; failover proceeds to the next provider.
- Invalid caller input and remote 4xx responses caused by that input are not
  provider-unavailability. Do not mask them by trying another provider.
- If every provider is unavailable, failover throws a final
  `PackingProviderUnavailable`.

Do not add an eager periodic health-check mechanism unless the selected remote
API explicitly requires one. The actual request is the health signal used by
failover.

### Caching and canonicalization

Cache the result of the entire ordered failover service, not just a remote
provider. Successful box selections and successful "no box fits" outcomes are
cached for a constant TTL of **five minutes**. Exceptions, malformed results,
and input-validation errors are never cached.

Generate the cache key from a canonical copy of the request so package order
does not affect the key. The canonicalizer must preserve all fields that can
affect selection and sort packages deterministically. It is a cache-key
responsibility, not a standalone preprocessing layer; do not mutate or
reorder the request passed to providers unless their individual contract
requires it.

Use an injectable cache abstraction/adapter. The initial implementation may be
an in-memory cache appropriate for the CLI process, unless the exercise
provides a required shared cache backend. Keep its interface small enough to
replace later.

### Exceptions and error mapping

Define exceptions according to their meaning to callers:

- `InvalidInput`: thrown while creating/validating input DTOs; mapped by the
  facade/CLI edge to a structured error output.
- `PackingProviderUnavailable`: thrown by a remote adapter after it translates
  transport/protocol failures; caught by the failover coordinator.
- `NoPackagingAvailable` (only if needed): thrown by a local adapter when its
  underlying packaging source is empty or unusable; mapped by the facade to a
  structured application error.

Adapter-private exceptions are allowed, but translate them to these semantic
exceptions before they cross an adapter seam. Do not make failover catch an
exception named for a specific remote adapter.

## Implementation steps

Each step should be completed and verified before moving to the next.

1. **Establish the command contract and typed transport models.**
   - Make `run.php` read one JSON document from its CLI argument and emit one
     JSON response to standard output.
   - Add input DTOs for the supplied product shape (`id`, `width`, `height`,
     `length`, `weight`) and validate JSON/type/required-field errors.
   - Add output DTOs for successful results and structured errors.
   - Verify valid sample JSON reaches the facade as typed data; malformed JSON
     and invalid product fields produce deterministic JSON errors.

2. **Introduce the application seam and exception vocabulary.**
   - Create the packing-service interface, typed request/result models, and
     the semantic exceptions above.
   - Implement a facade that maps input DTOs to the request, delegates once to
     the interface, and maps results/exceptions to output DTOs.
   - Verify the facade with a fake packing service for a selected box, a
     no-box result, unavailable providers, and invalid input.

3. **Implement the external API adapter.**
   - Add a remote packing-service adapter and small request/response DTOs
     dedicated to the external API's documented wire contract.
   - Configure endpoint, credentials, and timeouts outside application logic
     (the composition root/environment configuration); do not hard-code an
     undocumented API contract.
   - Translate transport failures, timeouts, 5xx responses, and invalid remote
     payloads to `PackingProviderUnavailable`. Preserve valid no-box results.
   - Verify serialization and error translation with a fake HTTP transport or
     fixture-based tests.

4. **Implement the local provider behind a repository adapter.**
   - Add a local packing-service adapter that obtains packaging data through a
     repository interface; isolate Doctrine's `EntityManager` within the
     Doctrine repository adapter.
   - Implement the required local selection behavior using the exercise's
     packing rules and seeded packaging data.
   - Treat an empty/unavailable local packaging source as `NoPackagingAvailable`
     if it is an application error, not as an API transport failure.
   - Verify it against the seeded data and against an empty source.

5. **Add ordered failover.**
   - Implement a failover packing service that accepts a non-empty ordered
     collection of packing-service providers.
   - Return the first successful selected-box or no-box result; continue only
     after `PackingProviderUnavailable`.
   - Verify first-provider success, provider failure followed by later success,
     valid no-box stopping the chain, non-availability of all providers, and
     propagation of non-availability business/input errors.

6. **Add canonical cache decoration.**
   - Implement a deterministic cache-key canonicalizer for packing requests.
   - Implement the cache decorator with a five-minute TTL, wrapping the
     failover service.
   - Cache selected-box and no-box results only.
   - Verify reordered equivalent product lists hit one key, a materially
     different request misses, entries expire after five minutes, and failed
     provider calls are not cached.

7. **Wire and document the production command.**
   - Construct providers in explicit priority order in `run.php`/bootstrap,
     then wrap them with failover and cache decorators before injecting them
     into the facade.
   - Update the README with setup, required configuration for the remote API,
     CLI invocation, and JSON output/error examples.
   - Run the sample command in the provided Docker environment and execute the
     project test suite.

## Acceptance criteria

- The application remains framework-free: no Symfony application/kernel or DI
  container is introduced.
- `run.php` has a clear JSON-in/JSON-out contract suitable for scripting.
- All replaceable infrastructure is manually wired through constructors at the
  composition root.
- Adding a new provider requires only an adapter implementing the
  packing-service interface and one composition-root entry; no failover logic
  changes are necessary.
- Provider order is deterministic; the first successful outcome, including a
  no-box result, is returned.
- Only provider-unavailability failures trigger another provider.
- Equivalent package sets in different orders share a cache entry; cache TTL
  is five minutes; failures are not cached.
- Input/output and remote-API DTOs remain separate, and Doctrine remains
  outside application/facade logic.
- Tests cover DTO validation, exception mapping, remote error translation,
  local selection, ordered failover, cache canonicalization, TTL behavior, and
  the CLI happy/error paths.
