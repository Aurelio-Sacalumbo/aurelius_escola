FROM php:8.2-apache

# Ativa o módulo de reescrita do Apache (útil para PWAs e rotas amigáveis)
RUN a2enmod rewrite

# Instala a extensão PDO MySQL para que o conexao.php funcione perfeitamente
RUN docker-php-ext-install pdo pdo_mysql

# Copia todos os ficheiros do projeto para a pasta pública do Apache
COPY . /var/var/www/html/

# Expõe a porta padrão do servidor web
EXPOSE 80