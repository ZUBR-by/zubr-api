# ZUBR API
###

[Swagger doc](https://api.zubr.in)

### System Requirements

* PHP >= 7.4
* PHP Extensions: bcmath,bz2,intl,gd,mbstring,mysql,zip,xml
* Composer
* MariadDB >= 10.4

### Documentation (Important to Read)

* [API Platform](https://api-platform.com/docs)
* [Symfony 5.x](https://symfony.com/doc/current/index.html#gsc.tab=0)

### Installation

1. Clone repo
    
2. Rename .env.dist to .env file:

    `mv .env.dist .env `

3. Change DATABASE_* parameters in .env file with your database configurations

4. Run(Docker-compose) and execute from the root of project: 
    
    `COMPOSE_PROJECT_NAME=zubr docker-compose -f infrastructure/docker-compose.yml up -d`
   
    `bash infrastructure/scripts/setup`

Or do it manually

5. Create database schema:   
    
    `php bin/console doctrine:database:create`

6. Execute database migrations 
    
    `php bin/console doctrine:migrations:migrate`

7. Configure maria db:
```
innodb_ft_min_token_size=1
innodb_ft_enable_stopword=0
ft_stopword_file=''
```
### Категории нарушений 

- Отказ в аккредитации наблюдателя - 0
- Допуск аккредитованного наблюдателя на участок - 1
- Недопуск аккредитованного наблюдателя на участок - 2
- Лишение наблюдателя аккредитации (удаление с участка) - 3 
- Принуждение избирателей к досрочному голосованию - 4 
- Несоблюдение комиссией сроков вывешивания протокола - 5
- Нарушение порядка голосования избирателя по месту нахождения - 6
- Несовпадение количества проголосовавших по подсчётам наблюдателя с данными из протокола комиссии -  7
- Несоответствие оформления помещения участка  нормам медицинской безопасности - 8
- Непрозрачный подсчёт голосов - 9
- Ограничение прав наблюдателя - 10
- Другое - 11

