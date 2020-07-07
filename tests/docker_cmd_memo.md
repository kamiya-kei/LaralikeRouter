
# test用のdockerコンテナ

+ コンテナイメージ生成: `docker build -f tests/Dockerfile -t apache-php7.1 .`
+ コンテナ生成: `docker run --name="laralikerouter" -d -p 8080:80 -v "${PWD}/:/var/www/html" apache-php7.1`
+ コンテナ削除: `docker rm laralikerouter`
