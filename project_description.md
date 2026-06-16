# Descrição do Projeto Aethos

Tudo aqui descrito abaixo, deverá ser produzido com 100% de eficácia, sem modelos de funções sem funcionar, sem, sem criação de mockups ou protótipos de baixa finalidade. Tudo descrito aqui abaixo, deve funcionar A RISCA, sem simulações. Deve ter suas funcionalidades completas, funcionando exatamente da forma descrita aqui, ou melhoradas por si.

## Descrição do Sistema
Sistema web de encontro de locais para esporte. Um sistema de mapa aberto podendo navegar entre o sistema como no google maps, navegando pelo mapa, que deve coletar (e pedir antecipadamente com um aviso) a localização do usuário, e colocar o mapa centralizado na localização do usuário.

O sistema deve ter a função de visualizar pontos próximos a sua localização no mapa e buscar pontos específicos em locais mais distantes (que serão todos os dois colocados pelo usuário-professor, e autorizado pelo administrador).

## Ferramentas e especificações
Site mobile first modularizado para desktop, de um sistema em jquery, tailwild e o resto ferramentas que achar melhor, fazendo no sistema. Serão aceitos bibliotecas frameworks externos, internos e personalizados, vindo de quaisquer fonte gratuita.

## Sistema de Login
Sistema de login com criação de senha (visível e não visível com o botão de ver e não ver com um olho fechado e aberto), verificação de e-mail, para verificar se aquele e-mail (normalmente g-mail, mas também incluir o resto) realmente existe. Acoplado a ele, um sistema de redefinição de senha, que manda um código de verificação para o e-mail do usuário (já verificado anteriormente), para redefinir a senha.

## Perfis

### Usuário
Pessoa que procura local de esportes na região, e acessa o sistema para encontrar os melhores professores, locais e etc. Ele também pode dar avaliações sobre os locais postos, e sobre usuários-professores específicos (como se fosse um Uber) - mas para isso, será necessário um login.

#### Login do usuário
Junto as funções básicas do sistema de Login, necessita-se de foto, nome, apelido (opcional), número de telefone, e-mail, e região com base no ddd (por exemplo, DDD 13, baixada Santista, ou DDD 11, São Paulo e Região).

### Professor (ou usuário-professor)
Divulgador dos locais. Ele em um formulário completo, após ser aprovado pelo administrador seu local, dojo, academia ou espaço de trabalho será alocado no mapa, para a visibilidade pública dos usuários que visitarem o site.

#### Login do Professor
Junto as funções básicas do sistema de Login, as mesmas exigências do usuário, mas com adições: endereço fixo e CPF (com verificação de se o CPF é real).

### Administrador
Usuário que administra os dados do sistema a nível profundo, podendo apagar usuários e professores, aprovar pedidos de cadastro de locais, acessar banco de dados completo do sistema para gerenciamento, remover comentários e avaliações.

#### Login do Administrador
Não necessita-se de cadastro. A Senha do administrador será única, e usada de acordo com esta lista de usuários (que poderão mudar a senha com o sistema de mudança de senha para administradores) que será posta, mas por enquanto, sem nenhum.

Após o primeiro login, o Sistema deve exigir a troca de senha do Administrador, para criar uma senha própria.

### Desenvolvedor
Usuário que administra os dados do sistema a nível completo, com painel do desenvolvedor para logs específicos de ações do sistema e descrições de seus erros, e todas as funções presentes nos poderes de um administrador.

#### Login do Desenvolvedor
Não necessita-se de cadastro. A Senha do desenvolvedor será única, e usada de acordo com esta lista de usuários (que poderão mudar a senha com o sistema de mudança de senha para desenvolvedores) abaixo:

##### Usuários Desenvolvedores
- Lorrany
- Arthur
- Nepo
- Leo
- Joaquim

##### Senha padrão inicial
`senha123`

Após o primeiro login, o Sistema deve exigir a troca de senha do Desenvolvedor, para criar uma senha própria.

---

## INTRANET LOCALHOST
Para administradores integrados ao sistema privado do setor de gerenciamento da empresa.

## Links e Armazenamento
- **Link do GitHub**: [https://github.com/nepotira/Aethos](https://github.com/nepotira/Aethos)
- **Local de armazenamento da identidade visual e landing page**: `E:\Desktop\NEPO\PROGRAMACAO\DSI\LaunchPad-Project\aethos_sports_landing_page`
