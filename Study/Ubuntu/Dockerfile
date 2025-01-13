FROM ubuntu

RUN apt update -y
RUN apt upgrade -y
RUN apt install systemctl -y
RUN apt-get install iputils-ping -y
# RUN apt install php libapache2-mod-php -y
RUN apt install apache2 -y
# RUN systemctl enable apache2
# RUN systemctl start apache2

# RUN echo 'we are running some cool things'

EXPOSE 80
EXPOSE 443

# WORKDIR /the/workdir/patdh

# COPY ./index.php /var/www/html
CMD apachectl -D FOREGROUND
# CMD ["rm", "/var/www/html/index.html"]