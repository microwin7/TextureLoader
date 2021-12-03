# TextureLoader
 
- null_requiredUrl - не указано в ссылке, что нужно skin или cloak
- null_data - не получены json данные, через POST запрос
- null_login - пустой логин
- invalid_login - логин не прошёл проверку по regex
- null_accessToken - пустой токен
- null_uuid - пустой uuid
- invalid_uuid - uuid не прошёл проверку по regex
- null_url - пустая ссылка
- invalid_url - url не прошёл проверку по фильтру
- invalid_response - запрос по ссылке был выполнен неуспешно, ошибка означает, что при запросе был получен ответ отличный от 200
- invalid_headers_url - тип заголовков загружаемых данных не является application/octet-stream или image/png
- slim_not_supported - ошибка скина, скин не может быть слим (так как версия как ты говорил 1.7.10)
- resource_not_png - Mime тип изображения не является png
- invalid_size_png - Размеры изображения не соответствуют настроенным в конфигурации скрипта
- not_found - При запросе MySQL данные не совпали, далее не может ничего происходить

При каждой из этих ошибок, скрипт не будет дальше выполняться
