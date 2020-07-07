
# test用のdockerコンテナ

+ コンテナイメージ生成: `docker build -f tests/Dockerfile -t apache-test-lararouter .`
+ コンテナ生成: `docker run --name="laralikerouter" -d -p 8080:80 -v "${PWD}/:/var/www/html" apache-test-lararouter`
+ コンテナ削除: `docker rm laralikerouter`
