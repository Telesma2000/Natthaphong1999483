FROM php:8.0-apache
# คำสั่งติดตั้ง mysqli เพื่อให้ PHP คุยกับ Database ได้
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
# เปิดใช้งาน mod_rewrite (เผื่อต้องใช้ในอนาคต)
RUN a2enmod rewrite