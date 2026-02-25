# Nyx Index Hub

Módulo Drupal para gerenciar grupos, projetos e indexação de documentos via API Google Gemini File Search.

## Recursos

- **Gestão de Grupos**: Cadastro de grupos com informações de contato
- **Gestão de Projetos**: Projetos vinculados a grupos com criação automática de stores na API
- **Integração com API**: Criação, listagem e exclusão de stores no Google Gemini File Search

## Configuração

### Variáveis de Ambiente (.env)

```env
URL_INDEX_HUB=https://generativelanguage.googleapis.com
API_KEY_INDEX_HUB=SuaChaveAqui
MODEL_INDEX_HUB=gemini-2.5-flash
```

### Instalação

1. Habilite o módulo: `drush en nyx_index_hub -y`
2. Configure as permissões em `/admin/people/permissions`
3. Acesse o módulo em `/admin/nyx`

## Permissões

- **administer nyx index hub**: Administração completa
- **manage nyx grupos**: Gerenciar grupos
- **manage nyx projetos**: Gerenciar projetos
- **view nyx grupos**: Visualizar grupos
- **view nyx projetos**: Visualizar projetos

## Estrutura de Dados

### Grupo
- Nome
- Contato
- Telefone
- Email

### Projeto
- Nome
- Grupo (referência)
- Descrição
- Status (Ativo/Inativo)
- Store Name (criado automaticamente)

## API Google Gemini File Search

Documentação: https://ai.google.dev/gemini-api/docs/file-search?hl=pt-br#rest

### Endpoints Utilizados

- `POST /v1beta/fileSearchStores` - Criar store
- `GET /v1beta/fileSearchStores` - Listar stores
- `DELETE /v1beta/fileSearchStores/{name}` - Deletar store
- `POST /v1beta/{store_name}/documents` - Upload de documento (em desenvolvimento)

### Formatos de Documentos Suportados

- `.md` - Markdown
- `.txt` - Texto simples
- `.pdf` - Documentos PDF
