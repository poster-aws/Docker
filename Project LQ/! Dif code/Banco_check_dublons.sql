SELECT
  n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,
  n11,n12,n13,n14,n15,n16,n17,n18,n19,n20,
  COUNT(*) AS total_tirages,
  GROUP_CONCAT(Tirage ORDER BY Tirage SEPARATOR ', ') AS tirages
FROM banco
GROUP BY
  n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,
  n11,n12,n13,n14,n15,n16,n17,n18,n19,n20
HAVING COUNT(*) > 1
ORDER BY total_tirages DESC;

-----------------------------------------------------------------

SELECT EXISTS(
  SELECT 1
  FROM banco
  GROUP BY
    n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,
    n11,n12,n13,n14,n15,n16,n17,n18,n19,n20
  HAVING COUNT(*) > 1
) AS has_duplicates;f