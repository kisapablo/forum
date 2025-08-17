FROM mysql:8.0

ADD config/schema.sql /docker-entrypoint-initdb.d
ADD config/procedures.sql /docker-entrypoint-initdb.d

EXPOSE 3306
