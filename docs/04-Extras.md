# 4. Extras

## Para desenvolver o pacote:

### 1. Instalação limpa do Laravel:

Embora seja uma biblioteca independente, StringArguments utiliza as ferramentas de teste do Laravel. Por isso, para desenvolver o pacote, a primeira coisa a ser feita é criar uma instalação limpa do Laravel:

```bash
$ composer create-project --prefer-dist laravel/laravel /caminho/do/projeto
$ cd /caminho/do/projeto
$ cp .env.example .env
$ php artisan key:generate
$ chmod 777 -Rf /caminho/do/projeto/bootstrap/cache
$ chmod 777 -Rf /caminho/do/projeto/storage
```

### 2. Diretório de desenvolvimento

Na raiz do projeto Laravel, crie o diretório 'packages'. Este diretório será usado para desenvolver pacotes:

```bash
$ mkdir /caminho/do/projeto/packages
```

### 3. Obtendo o pacote para desenvolvimento

No novo diretório de pacotes, é preciso criar a estrutura do pacote 'string-arguments'. O formato deve ser '[vendor]/[pacote]', ou seja, a estrutura do pacote ficará assim '/plexi/string-arguments':

```bash
$ cd /caminho/do/projeto/packages
$mkdir -p plexi/string-arguments
```

No diretório 'string-arguments', faça um clone do repositório:

```bash
$ cd /caminho/do/projeto/packages/plexi/string-arguments
$ git clone https://github.com/rpdesignerfly/string-arguments.git .
```

### 4. Configurando o Laravel para usar o pacote

No arquivo "composer.json", abaixo da seção 'config', adicione 'minimum-stability' como 'dev' e o repositório apontando para o diretório './packages/plexi/string-arguments/'.

> **Atenção:**
> Não esqueça da barra (/) no final:

```php
{
    "name": "laravel/laravel",
    "description": "The Laravel Framework.",

    ...


    "config": {
        ...
    },

    "minimum-stability" : "dev",
    "repositories": [
        {"type": "path", "url": "./packages/plexi/string-arguments/"}
    ]

}
```

Com o repositório configurado, use normalmente o comando para instalação:

```bash
$ cd /caminho/do/projeto
$ composer require plexi/string-arguments
```


Em seguida, basta executar a instalação ou atualização do composer para que o pacote seja
adicionado ao autoloader do composer:

```bash
$ cd /caminho/do/projeto
$ composer install
```

ou

```bash
$ cd /caminho/do/projeto
$ composer update
```

## Sumário

  1. [Sobre](01-About.md)
  2. [Instalação](02-Installation.md)
  3. [Como Usar](03-Usage.md)
  4. [Extras](04-Extras.md)
