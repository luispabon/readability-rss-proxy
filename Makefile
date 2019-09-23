SHELL=/bin/bash
MKCERT_VERSION=v1.3.0
MKCERT_LOCATION=$(PWD)/bin/mkcert
RSS_PROXY_HOST=rss-proxy.local

ifndef BUILD_TAG
	BUILD_TAG:=$(shell date +'%Y-%m-%d-%H-%M-%S')-$(shell git rev-parse --short HEAD)
endif

# linux-amd64, darwin-amd64, linux-arm
# On windows, override with windows-amd64.exe
ifndef BINARY_SUFFIX
	BINARY_SUFFIX:=$(shell [[ "`uname -s`" == "Linux" ]] && echo linux || echo darwin)-amd64
endif

ifndef BINARY_ARCH
	BUILD_TAG:=$(shell date +'%Y-%m-%d-%H-%M-%S')-$(shell git rev-parse --short HEAD)
endif

echo-build-tag:
	echo $(BUILD_TAG)

dev-init: clean install-mkcert create-certs clean-hosts init-hosts install-dependencies start init-db load-fixtures

install-dependencies:
	docker-compose run php-fpm composer -o install
	@echo "*** We need root to fix permissions for the vendor/ folder on some systems"
	sudo chown $(shell id -u):$(shell id -g) vendor -Rf

install-mkcert:
	@echo "Installing mkcert for OS type ${BINARY_SUFFIX}"
	@if [[ ! -f '$(MKCERT_LOCATION)' ]]; then curl -sL 'https://github.com/FiloSottile/mkcert/releases/download/$(MKCERT_VERSION)/mkcert-$(MKCERT_VERSION)-$(BINARY_SUFFIX)' -o $(MKCERT_LOCATION); chmod +x $(MKCERT_LOCATION);	fi;
	bin/mkcert -install

create-certs:
	bin/mkcert -cert-file=infrastructure/local/local.pem -key-file=infrastructure/local/local.key.pem $(RSS_PROXY_HOST)

clean-hosts:
	sudo bin/hosts remove --force *$(RSS_PROXY_HOST) > /dev/null 2>&1 || exit 0

init-hosts: clean-hosts
	sudo bin/hosts add 127.0.0.1 $(RSS_PROXY_HOST)

start:
	docker-compose up -d

stop:
	docker-compose kill

clean:
	docker-compose down

build-images:
	docker build --pull --target=backend-deployment  -t rss-proxy-php-fpm .
	docker build --pull --target=frontend-deployment -t rss-proxy-nginx   .

tag-images:
	docker tag rss-proxy-nginx eu.gcr.io/auron-infrastructure/rss-proxy-nginx:$(BUILD_TAG)
	docker tag rss-proxy-php-fpm eu.gcr.io/auron-infrastructure/rss-proxy-php-fpm:$(BUILD_TAG)

push-images:
	docker push eu.gcr.io/auron-infrastructure/rss-proxy-nginx:$(BUILD_TAG)
	docker push eu.gcr.io/auron-infrastructure/rss-proxy-php-fpm:$(BUILD_TAG)

build-and-push: build-images tag-images push-images

deploy:
	cp infrastructure/kubernetes/deployment.yaml /tmp/rss-proxy-deployment-$(BUILD_TAG).yaml
	sed -i "s/latest/$(BUILD_TAG)/g" /tmp/rss-proxy-deployment-$(BUILD_TAG).yaml

	kubectl apply -f /tmp/rss-proxy-deployment-$(BUILD_TAG).yaml
	rm /tmp/rss-proxy-deployment-$(BUILD_TAG).yaml

rollback:
	kubectl rollout undo deployment.v1.apps/rss-proxy

init-db: start
	docker-compose exec postgres bin/wait-for-db.sh
	docker-compose exec php-fpm bin/console doctrine:schema:drop --force
	docker-compose exec php-fpm bin/console doctrine:schema:create

load-fixtures: start
	docker-compose exec postgres bin/wait-for-db.sh
	docker-compose exec php-fpm bin/console doctrine:fixtures:load -n

dev-storage-public:
	gsutil acl ch -r -u AllUsers:R "gs://rss-proxy-dev/*"
