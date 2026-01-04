all:

phar:
	phar-composer build .

buildimage:
	docker build -f Containerfile -t docker.io/vitexsoftware/kimai2abraflexi:latest .

buildx:
	docker buildx build -f Containerfile . --push --platform linux/arm/v7,linux/arm64/v8,linux/amd64 --tag docker.io/vitexsoftware/kimai2abraflexi:latest

drun:
	docker run --env-file .env docker.io/vitexsoftware/kimai2abraflexi:latest

.PHONY: validate-multiflexi-app
validate-multiflexi-app: ## Validates the multiflexi JSON
	@if [ -d multiflexi ]; then \
		for file in multiflexi/*.multiflexi.app.json; do \
			if [ -f "$$file" ]; then \
				echo "Validating $$file"; \
				multiflexi-cli app validate-json --file="$$file"; \
			fi; \
		done; \
	else \
		echo "No multiflexi directory found"; \
	fi
