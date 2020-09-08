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


### Wireframes

```plantuml
(*) --> "{{
salt
{
<&menu> | <&person>
{^Map 
<&task> Участак №124 (94/15)
.
прадпрыемства 456<&map-marker>
.
<b><&pin> участак 789
}
<&warning> Парушэнняў <b>509 <&graph> | <&bell>
<&eye> Назіральнікаў <b>7260 <&chat> | <&plus>
--
<&pie-chart> <u>Статыстыка
[Я галасую]
[Я назіраю]
[Не ўдзельнічаю]

}
}}
" as map


map -left> "{{
salt
{
<&arrow-left>
<&clock> Апошния парушэнні
<&eye> Маё назіранне
<&globe> Мова <&caret-right> Аўта/Бел/Рус/Eng
}
}}" as menu

menu -> map

map -> "{{
salt
{<&arrow-left> Не удзельнічаю
--
Чаму?
[] Няма добрага кандыдата
[] Выбары фальсіфікуюцца
[] Каранавірус
[] Няма магчымасці: праца, ад'езд
[] Іншае
{SI
Іншае
.
}
}
}}"

map -> "{{
salt
{<&arrow-left> Я назіраю
--
Абраць участак
() Дзе недахоп
() Бліжэйшы <&target>
() Ужо маю накіраванне
}
}}" as observe

map -> "{{
salt
{<&arrow-left> Я галасую
--
За каго?
() 
()
()
() Супраць ўсіх
--
"Нумар ці адрас участка <&target>"
"Дзень і час галасавання <&calendar><&clock>"
--
[] Ананімны фотадоказ <&info> <&camera-slr>
}
}}"


map --> "{{
salt
{
<&task><b>Участак №124</b>       Акруга 94/15
<&map-marker> <u>г. Минск, ул. Якубова, 12</u> ГУО «Средняя школа № 15 г. Минска»
--
<&eye> Назіральнікаў: 2, <&thumb-down> Парушэнняў 4, <&sun> Дазволаў 1, <&people> Яўка: пратакол 80%, падлік: 15% 
==|==
Гаврикова Валентина Фёдоровна <&flag> (Старшыня) | | <&chevron-top>
<i><&people> <u>от Минской городской организации Белорусской социально-спортивной партии 
<i><&briefcase> <u>Директор ГУО "Средняя школа №15 г.Минска"
--
Мисникевич Лариса Ивановна <&loop> (Намеснік) | <&chevron-top>
<i><&people> <u>от трудового коллектива ГУО «Средняя школа № 15 г. Минска»
<i><&briefcase> <u>Заместитель директора по учебной работе ГУО "Средняя школа №15 г.Минска"
--
Пономарева Елена Ивановна <&calculator> (Сакратар) | <&chevron-top>
<i><&people> <u>от Ленинской районной организации РОО «Белая Русь»
<i><&briefcase> <u>Заместитель директора по учебной работе ГУО "Средняя школа №15 г.Минска"
--
Гончар Ольга Николаевна | <&chevron-bottom>
Денисевич Наталья Федоровна | <&chevron-bottom>
Евдокимова Ольга Михайловна | <&chevron-bottom>
Курылович Александра Александровна | <&chevron-bottom>
Кухарчик Ирина Ивановна | <&chevron-bottom>
Мартинович Елена Анатольевна | <&chevron-bottom>
Мурина Татьяна Леонидовна | <&chevron-bottom>
Поскробка Екатерина Геннадьевна | <&chevron-top>
<i><&people> от граждан путем подачи заявления
<i><&briefcase> <u>учитель английского языка ГУО "Средняя школа №15 г.Минска"
--
Редько Ирина Николаевна | <&chevron-bottom>
Синегуб Наталья Николаевна | <&chevron-bottom>

}
}}
" as station
observe --> station
station --> "{{
salt
{
<b><&thumb-down> Парушэнне | <&x>
^Што адбылося?^ | <&camera-slr> <&link-intact>
{SI
Дэталі
.
.
}
{SI
Удзельнікі
.
}
{SI
Сведкі
.
}

() Пададзена скарга
[] Старшыне
[] У акругу
[] У міліцыю
[] У пракуратуру
() Адмова прыняць
() Не было магчымасці падаць

[Адправіць] 
}
}}
"

station --> "{{
salt
{
<b><&list> Вынікі | <&x>
{^ Пратакол <&camera-slr>
"Усяго"
"Прагаласавала за дзень"
"Засталося"
}
{^ Падлік <&camera-slr>
"Прагаласавала за дзень"
}
[] Дазвол бачыць спісы
{SI
Дэталі
Інфармацыя пра іншых назіральнікаў
Падазроныя людзі, падслуханае,
Бачныя галасы, хатняе галасаванне.
}
[Адправіць] 
}
}}
"

station --> "{{
salt
{
<b><&list-rich> Выніковы падлік | <&x>
[] Дазвол на фота
[] Дазвол на відэа
[] Дазвол бачыць галасы
Пратакол старонка 1 <&camera-slr> <&link-intact>
Пратакол старонка 2 <&camera-slr> <&link-intact>
{SI
Іншае
.
}
[Адправіць] 
}
}}
"

```
### Database diagram
```plantuml
object commission <<Камісія>> {
    {static} id int(11) AI PK
    code varchar(500)
    type int: 1 - ЦВК, 2 - АВК/ТВК, 3 - УВК  
    name text 
    description text 
    location text 
    longitude decimal(11,8) 
    latitude decimal(11,8)
    {abstract} created_at datetime 
    {abstract} updated_at datetime
}

object member <<Член камісіі>> {
    {static} id int(11) AI PK 
    # comission_id FK 
    # referral_id FK 
    # employer_id FK 
    position_type int: 0 - просты, 1 - Старшыня, 2 - Зам, 3 - Сакратар 
    full_name varchar(500) 
    photo_url varchar(1000) 
    description text 
    region int: 1 - Brest, ..., 7 - Minsk
    {abstract} created_at datetime 
    {abstract} updated_at datetime
}

object organization <<Арганізацыя>> {
    {static} id int(11) AI PK 
    name text 
    type int: 0 - N/A, 1 - official, 2 - opposite 
    url text 
    description text 
    longitude decimal(11,8) 
    latitude decimal(11,8) 
    location text
    region int: 1 - Brest, ..., 7 - Minsk
    {abstract} created_at datetime 
    {abstract} updated_at datetime
}

object user <<Карыстальнік>> {
    {static} id int(11) AI PK 
    username varchar(255) 
    email varchar(255) 
    password varchar(255) 
    location varchar(1000)
    {abstract} created_at datetime 
    {abstract} updated_at datetime
}

comission <-- member : comission_id
member --> organization : referral_id <i> накіраваны
member --> organization : employer_id <i> працуе ў
```
