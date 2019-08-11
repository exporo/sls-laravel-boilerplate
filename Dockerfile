FROM registry.gitlab.com/exporo/nginx-php:7.2.12

RUN apk -v --update --no-cache add openssh mysql-client zip curl tar && \
    sed -i s/#PermitRootLogin.*/PermitRootLogin\ without-password/ /etc/ssh/sshd_config

COPY ./docker/manifest/ /
RUN chmod 755 /entrypoint.sh
ADD ./application/ /var/www/html
RUN chmod -R 777 /var/www/html/

WORKDIR /var/www/html

EXPOSE 8080
EXPOSE 22

CMD ["/bin/bash", "/entrypoint.sh"]
