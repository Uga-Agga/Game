INSERT INTO `game05`.`OldTribes` 
  SELECT `game_runde6`.`Tribe`.`tag` as tag, `game_runde6`.`Tribe`.`password` as password, 0 as used, `game_runde6`.`RankingTribe`.`points_rank` as points_rank
  FROM  `game_runde6`.`Tribe`
  JOIN `game_runde6`.`RankingTribe` ON `game_runde6`.`RankingTribe`.`tribe` = `game_runde6`.`Tribe`.`tag`
