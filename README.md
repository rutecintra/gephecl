# Site GEPHECL – UFAL CEDU

Site em HTML, CSS e JavaScript do **Grupo de Estudo e Pesquisa em História da Educação, Cultura e Literatura (GEPHECL)**, para publicação no **UFAL Sites (Hostinger)**.

## Estrutura do projeto

```
gephecl/
├── index.html          # Página inicial
├── membros.html        # Membros
├── fotos.html          # Galeria de fotos
├── admin/
│   └── index.php       # Painel admin (login + upload de fotos)
├── projetos-pesquisa.html
├── dissertacoes.html
├── catalogo-fontes.html
├── livros-fragmentos.html
├── contato.html
├── links.html
├── css/
│   └── site.css
├── js/
│   └── site.js
├── uploads/
│   ├── home/
│   │   └── capa.jpg    # Capa da primeira página (opcional)
│   ├── fotos/          # Fotos da galeria + manifest.json (gerenciados pelo admin)
│   └── logo-gephecl.png   # Logo do grupo (opcional)
└── README.md
```

## Upload no Hostinger (UFAL Sites)

Conforme o **Manual de Hospedagem de Sites - UFAL SITES**:

1. **Acesso:** https://hosting.ufal.br/ (CPF e senha institucional).

2. **Pasta pública:** Somente o que estiver dentro da pasta **`public`** do seu site fica visível para os visitantes. Você deve enviar todo o conteúdo deste projeto **para dentro da pasta `public`**.

3. **Envio de arquivos:**
   - Entre no seu site → aba **Arquivos** → abra a pasta **`public`**.
   - Clique em **Upload**.
   - Você pode:
    - **Enviar um ZIP:** coloque todos os arquivos (HTML, `css/`, `js/`, `uploads/`, `admin/`) em um arquivo `.zip`. Envie o ZIP e marque **“Extrair arquivos comprimidos?”** para que os arquivos sejam extraídos dentro de `public`.
    - **Ou enviar pasta por pasta:** criar em `public` as pastas `css`, `js`, `uploads`, `uploads/home`, `uploads/fotos`, `admin` e enviar os arquivos em cada uma.

4. **Estrutura final no servidor (dentro de `public`):**

   ```
   public/
   ├── index.html
   ├── membros.html
   ├── fotos.html
   ├── ... (demais .html)
   ├── css/
   │   └── site.css
   ├── js/
   │   └── site.js
   ├── admin/
   │   └── index.php
   ├── uploads/
   │   ├── home/
   │   │   └── capa.jpg
   │   ├── fotos/
   │   │   └── manifest.json
   │   └── (logo e outras imagens, se usar)
   ```

5. **Importante:** O `index.html` deve ficar na **raiz da pasta `public`** para que a página inicial abra ao acessar a URL do site.

## Como editar o conteúdo

- **Textos e listas:** edite os arquivos `.html` diretamente (por exemplo: dissertações em `dissertacoes.html`, links em `links.html`).
- **Estilos:** altere `css/site.css`.
- **Comportamento (menu mobile, slider):** altere `js/site.js`.
- **Imagens da galeria:** use o painel em `admin/index.php` para enviar as imagens pela interface (recomendado).
- **Capa da home:** coloque a imagem principal em `uploads/home/capa.jpg` (ela aparece automaticamente no topo da página inicial).

## Painel admin da galeria

- URL do painel: `https://SEU-SITE/admin/index.php`
- Função: login + upload de múltiplas imagens pela interface.
- O painel salva as imagens em `uploads/fotos/` e atualiza automaticamente o arquivo `uploads/fotos/manifest.json`, que é lido por `fotos.html`.
- Senha inicial do admin: `admin123`.
- **Importante:** antes de publicar, altere a senha no arquivo `admin/index.php` (variável `$adminPasswordHash`).

## Navegação do site

- **Início** – Banner de eventos, texto do grupo, identidade.
- **Membros** – Página de membros; submenu: Galeria de Fotos.
- **Produção Acadêmica** – Projetos de Pesquisa; Dissertações.
- **Catálogo de Fontes** – Página do catálogo.
- **Livros e Fragmentos (1840-1963)** – Lista por categoria (ABC das Alagoas, Almanaques).
- **Contato** – Local, horário de reuniões, Instagram.
- **Links** – Links externos (IHGAL, Arquivo Público, SBHE, etc.).

O site é responsivo e os menus com setinha viram dropdown ao clicar em telas menores.
