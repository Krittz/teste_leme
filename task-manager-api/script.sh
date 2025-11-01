#!/bin/bash

# ═══════════════════════════════════════════════════════════
# Script para criar estrutura de diretórios do projeto
# ═══════════════════════════════════════════════════════════

echo "Criando estrutura de diretórios..."

# Storage
mkdir -p storage/logs
mkdir -p storage/cache
touch storage/logs/.gitkeep
touch storage/cache/.gitkeep

# Uploads
mkdir -p public/uploads/projects
mkdir -p public/uploads/tasks
touch public/uploads/.gitkeep
touch public/uploads/projects/.gitkeep
touch public/uploads/tasks/.gitkeep

# Logs files (vazios)
touch storage/logs/app.log
touch storage/logs/error.log

echo "Estrutura criada com sucesso!"
echo ""
echo "Diretórios criados:"
echo "  - storage/logs/"
echo "  - storage/cache/"
echo "  - public/uploads/projects/"
echo "  - public/uploads/tasks/"