FROM php:8.2-apache

# Ativa o módulo de reescrita do Apache
RUN a2enmod rewrite

# Instala a extensão PDO MySQL para o conexao.php
RUN docker-php-ext-install pdo pdo_mysql

# 🛠️ CORREÇÃO CRÍTICA: Cria um ficheiro de configuração próprio para o DirectoryIndex
RUN echo "DirectoryIndex Principal.html" > /etc/apache2/conf-available/pwa-index.conf \
    && a2enconf pwa-index

# Copia os ficheiros para o diretório padrão do Apache
COPY . /var/www/html/

# Dá as permissões de leitura corretas ao Apache
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80