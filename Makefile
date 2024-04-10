all:

phar:
	phar-composer build .

buildimage:
	docker build -f Containerfile -t docker.io/vitexsoftware/kimai2abraflexi:latest .

buildx:
	docker buildx build -f Containerfile . --push --platform linux/arm/v7,linux/arm64/v8,linux/amd64 --tag docker.io/vitexsoftware/kimai2abraflexi:latest

drun:
	docker run --env-file .env docker.io/vitexsoftware/kimai2abraflexi:latest
