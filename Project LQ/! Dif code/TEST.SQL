CALL check_tout_distribution(1,3,5,7,9,12,14,16,18,20,22,24);


UPDATE Q2_combo_stats_order c
JOIN Q2_stats_order s
  ON c.n1 = s.n1
 AND c.n2 = s.n2
 AND c.Tirage = s.Tirage
SET c.max = s.max;