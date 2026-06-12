# Deploy pelo cPanel

Este projeto esta preparado para usar o Git Version Control do cPanel.

## Arquivos que ficam fora do Git

Os itens abaixo nao devem ir para o repositorio:

- `includes/config.php`
- `credenciais/`
- `uploads/`
- `cache/`
- `error_log`
- `vendor/`

Eles continuam existindo no servidor e nao sao sobrescritos pelo deploy. O `vendor/` ficou fora do Git porque o projeto nao tem `composer.json` na raiz e a pasta atual e muito grande para um fluxo leve de deploy.

## Caminho de producao

O deploy esta configurado em `.cpanel.yml` para copiar os arquivos para:

```text
/home1/plani555/sathcon.sathgold.com.br/
```

Esse caminho foi inferido pelo `error_log` existente no projeto.

## Fluxo recomendado

1. Fazer alteracoes localmente.
2. Rodar validacao local, quando possivel.
3. Criar commit:

```bash
git add .
git commit -m "Mensagem do commit"
```

4. Enviar para GitHub/GitLab:

```bash
git push origin main
```

5. No cPanel, abrir `Files > Git Version Control`.
6. No repositorio, clicar em `Update from Remote`.
7. Clicar em `Deploy HEAD Commit`.

## Primeiro setup no cPanel

1. Crie ou conecte um repositorio no cPanel Git Version Control.
2. Garanta que o arquivo `.cpanel.yml` esteja na raiz do repositorio.
3. Antes do primeiro deploy, confirme que estes arquivos/pastas ja existem no servidor:

```text
includes/config.php
credenciais/
uploads/
cache/
vendor/
```

4. Faca backup dos arquivos e do banco antes do primeiro deploy.
