FROM mysql:8.0

ADD config/*.sql /docker-entrypoint-initdb.d

EXPOSE 3306
