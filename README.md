# FollowsComparison

自分用なのでいろいろ適当  
2つの時点でのフォロー、フォロワーを比較してリム、新規フォロー等を表示します。

## Usage
### Require
phpdotenv
.envはCompareFollows直下
### DB
    CREATE TABLE `followers` (
      `created_at` datetime NOT NULL,
      `myid` bigint(20) NOT NULL,
      `userid` bigint(20) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `friends` (
      `created_at` datetime NOT NULL,
      `myid` bigint(20) NOT NULL,
      `userid` bigint(20) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `snapshots` (
      `no` int(11) NOT NULL,
      `created_at` datetime NOT NULL,
      `userid` bigint(20) NOT NULL,
      `screenname` varchar(15) NOT NULL,
      `follow` int(11) NOT NULL,
      `follower` int(11) NOT NULL,
      `tweet` int(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `users` (
      `userid` bigint(20) NOT NULL,
      `screenname` varchar(15) NOT NULL,
      `name` varchar(20) NOT NULL,
      `updated_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ALTER TABLE `snapshots`
      ADD PRIMARY KEY (`no`);

    ALTER TABLE `snapshots`
      MODIFY `no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

    ALTER TABLE `users`
      ADD PRIMARY KEY (`userid`);
