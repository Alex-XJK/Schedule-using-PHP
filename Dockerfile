FROM php:8.3.24-apache
LABEL authors="Alex Jiakai Xu <jiakai.xu@columbia.edu>"

# Set EST timezone
ENV TZ=America/New_York
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files to the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Open port (default 80)
EXPOSE 80
