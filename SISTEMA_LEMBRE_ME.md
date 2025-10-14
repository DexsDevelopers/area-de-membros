# 🧠 Sistema "Lembre-me" - HELMER ACADEMY

## 📋 Visão Geral

Sistema completo de autenticação "Lembre-me" que permite aos usuários permanecerem logados por 30 dias sem precisar digitar usuário e senha novamente.

## 🔧 Arquivos do Sistema

### 1. **create_remember_me_table.php**
- **Função:** Cria a tabela `remember_tokens` no banco de dados
- **Uso:** Execute uma vez para configurar o sistema
- **Acesso:** Apenas administradores

### 2. **remember_me_functions.php**
- **Função:** Contém todas as funções do sistema
- **Inclui:**
  - `generateRememberToken()` - Gera token seguro
  - `createRememberToken()` - Cria token para usuário
  - `validateRememberToken()` - Valida token existente
  - `revokeRememberToken()` - Revoga token específico
  - `revokeAllUserTokens()` - Revoga todos os tokens do usuário
  - `cleanExpiredTokens()` - Remove tokens expirados
  - `checkRememberMe()` - Verifica e autentica via cookie
  - `setRememberMeCookie()` - Define cookie seguro
  - `removeRememberMeCookie()` - Remove cookie

### 3. **processa_login.php** (Atualizado)
- **Função:** Processa login com suporte a "Lembre-me"
- **Recursos:**
  - Verifica checkbox "Lembre-me"
  - Cria token se solicitado
  - Define cookie seguro
  - Log de ações

### 4. **auto_remember_me.php**
- **Função:** Verificação automática em páginas protegidas
- **Uso:** `require_once 'auto_remember_me.php';`
- **Recursos:**
  - Verifica token automaticamente
  - Autentica usuário se token válido
  - Redireciona para login se necessário

### 5. **logout_secure.php**
- **Função:** Logout seguro com revogação de tokens
- **Recursos:**
  - Revoga todos os tokens do usuário
  - Remove cookie "Lembre-me"
  - Limpa sessão completamente
  - Log de ações

### 6. **manage_remember_me.php**
- **Função:** Interface para usuário gerenciar tokens
- **Recursos:**
  - Lista todos os tokens ativos
  - Mostra informações de cada token
  - Permite revogar tokens individuais
  - Permite revogar todos os tokens
  - Interface moderna e responsiva

### 7. **test_remember_me.php**
- **Função:** Teste completo do sistema
- **Recursos:**
  - Verifica status da tabela
  - Testa todas as funções
  - Mostra status de cookies
  - Links para todas as funcionalidades

## 🗄️ Estrutura da Tabela

```sql
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    user_agent TEXT,
    ip_address VARCHAR(45),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_active (is_active)
);
```

## 🔒 Segurança

### **Tokens Seguros:**
- Tokens de 64 caracteres hexadecimais
- Armazenados como hash SHA-256 no banco
- Expiração automática em 30 dias
- Rastreamento de IP e User-Agent

### **Cookies Seguros:**
- HttpOnly: Não acessível via JavaScript
- Secure: Apenas em HTTPS
- Path: Aplicável a todo o site
- Expiração: 30 dias

### **Limpeza Automática:**
- Tokens expirados são removidos automaticamente
- 5% de chance de limpeza a cada requisição
- Log de ações de limpeza

## 🚀 Como Implementar

### **1. Configuração Inicial:**
```bash
# 1. Execute para criar a tabela
https://helmer-mbs.site/create_remember_me_table.php

# 2. Teste o sistema
https://helmer-mbs.site/test_remember_me.php
```

### **2. Em Páginas Protegidas:**
```php
<?php
session_start();
require_once 'auto_remember_me.php';
// Resto do código da página
?>
```

### **3. No Login:**
- O formulário já tem a opção "Lembre-me"
- O `processa_login.php` já está configurado
- Funciona automaticamente

### **4. No Logout:**
```php
// Use logout_secure.php em vez de logout.php
header('Location: logout_secure.php');
```

## 📱 Interface do Usuário

### **Login:**
- Checkbox "Lembre-me" já implementado
- Funciona automaticamente
- Visual moderno e responsivo

### **Gerenciamento:**
- Acesse: `https://helmer-mbs.site/manage_remember_me.php`
- Lista todos os dispositivos conectados
- Informações detalhadas de cada token
- Revogação individual ou em massa

## 🔍 Monitoramento

### **Logs Automáticos:**
- Criação de tokens
- Validação de tokens
- Revogação de tokens
- Limpeza de tokens expirados
- Erros do sistema

### **Verificação de Status:**
- Use `test_remember_me.php` para diagnóstico
- Verifica tabela, funções e cookies
- Mostra estatísticas do sistema

## 🛠️ Manutenção

### **Limpeza Manual:**
```php
// Limpar tokens expirados
cleanExpiredTokens();

// Revogar todos os tokens de um usuário
revokeAllUserTokens($user_id);
```

### **Monitoramento:**
- Verifique logs regularmente
- Monitore uso de tokens
- Revise tokens suspeitos

## 🎯 Benefícios

### **Para Usuários:**
- ✅ Não precisa digitar senha por 30 dias
- ✅ Acesso rápido e conveniente
- ✅ Controle total sobre dispositivos
- ✅ Segurança mantida

### **Para Administradores:**
- ✅ Redução de suporte
- ✅ Melhor experiência do usuário
- ✅ Sistema seguro e confiável
- ✅ Monitoramento completo

## 🔧 Troubleshooting

### **Problemas Comuns:**

1. **Token não funciona:**
   - Verifique se a tabela existe
   - Confirme se as funções estão incluídas
   - Verifique logs de erro

2. **Cookie não é definido:**
   - Confirme se HTTPS está ativo
   - Verifique configurações do servidor
   - Teste em navegador diferente

3. **Usuário não é autenticado:**
   - Verifique se `auto_remember_me.php` está incluído
   - Confirme se a sessão está ativa
   - Verifique redirecionamentos

### **Debug:**
- Use `test_remember_me.php` para diagnóstico
- Verifique logs do servidor
- Teste em ambiente de desenvolvimento

## 📊 Estatísticas

O sistema mantém estatísticas automáticas:
- Total de tokens criados
- Tokens ativos por usuário
- Último uso de cada token
- Dispositivos conectados
- Limpeza automática

## 🎉 Conclusão

O sistema "Lembre-me" está completamente implementado e funcional. Oferece:

- ✅ **Segurança máxima** com tokens criptografados
- ✅ **Experiência do usuário** otimizada
- ✅ **Controle total** sobre dispositivos
- ✅ **Monitoramento completo** do sistema
- ✅ **Interface moderna** e responsiva

**Sistema pronto para produção!** 🚀
