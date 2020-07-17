
# test用のdockerコンテナ操作

+ コンテナイメージ生成: `docker build -f tests/Dockerfile -t apache-php7.1 .`
+ コンテナ生成: `docker run --name="laralikerouter" -d -p 8080:80 -v "${PWD}/:/var/www/html" apache-php7.1`
+ コンテナ削除: `docker rm laralikerouter`



# git tag

+ タグ一覧: `git tag`
+ タグ追加&push : `git tag v1.0.0`, `git push origin v1.0.0`
+ 全タグ一括push: `git push origin --tags`
+ タグ削除&push:  `git tag -d v1.0.0` , `git push --delete origin v1.0.0`