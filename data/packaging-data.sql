BEGIN;
INSERT INTO packaging (id, width, height, length, small_edge, middle_edge, long_edge, max_weight) VALUES (1, 2.5, 3.0, 1.0, 1.0, 2.5, 3.0, 20);
INSERT INTO packaging (id, width, height, length, small_edge, middle_edge, long_edge, max_weight) VALUES (2, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 20);
INSERT INTO packaging (id, width, height, length, small_edge, middle_edge, long_edge, max_weight) VALUES (3, 2.0, 2.0, 10.0, 2.0, 2.0, 10.0, 20);
INSERT INTO packaging (id, width, height, length, small_edge, middle_edge, long_edge, max_weight) VALUES (4, 5.5, 6.0, 7.5, 5.5, 6.0, 7.5, 30);
INSERT INTO packaging (id, width, height, length, small_edge, middle_edge, long_edge, max_weight) VALUES (5, 9.0, 9.0, 9.0, 9.0, 9.0, 9.0, 30);
COMMIT;
