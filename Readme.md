# Integração Api Galax Pay 

- PHP 7.3

A classe de controle da integração com a Galax Pay está em:
- source/App/Api/v1/Pay.php

O arquivo de configurações de acesso ao serviço da 
Galax Pay está em:

- source/Boot/Config.php

A configuração é realizado através das constantes

- CONF_GALAXPAY_DEV [endpoint]
- CONF_GALAX_ID [ id credencial da conta na Galax Pay ]
- CONF_GALAX_HASH [ hash credencial da conta na Galax Pay ]