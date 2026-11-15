#!/usr/bin/env bash
#
# gha-ssh.sh — pembungkus SSH untuk GitHub Actions (dipakai workflow "VPS").
# Menjalankan satu perintah di VPS memakai kunci privat ATAU kata sandi SSH.
#
# Pemakaian (di runner):
#   VPS_HOST=... VPS_USER=root VPS_SSH_PASSWORD=... bash deploy/gha-ssh.sh 'uptime; df -h /'
#   VPS_HOST=... VPS_USER=root VPS_SSH_KEY="$(cat key)" bash deploy/gha-ssh.sh '...'
#
# Keluaran perintah diteruskan ke stdout, stderr ke stderr. Exit code mengikuti ssh.
#
set -uo pipefail

CMD="${1:-}"
if [ -z "$CMD" ]; then
    echo "pemakaian: gha-ssh.sh '<perintah remote>'" >&2
    exit 2
fi

HOST="${VPS_HOST:?VPS_HOST belum diisi}"
USER_NAME="${VPS_USER:-root}"
PORT="${VPS_PORT:-22}"
mkdir -p ~/.ssh && chmod 700 ~/.ssh

ssh-keyscan -T 10 -p "$PORT" -H "$HOST" >> ~/.ssh/known_hosts 2>/dev/null || true

COMMON=(-o "StrictHostKeyChecking=accept-new" -o "ConnectTimeout=25" -p "$PORT" "${USER_NAME}@${HOST}")

if [ -n "${VPS_SSH_KEY:-}" ]; then
    printf '%s\n' "$VPS_SSH_KEY" > ~/.ssh/id_deploy
    chmod 600 ~/.ssh/id_deploy
    exec ssh -i ~/.ssh/id_deploy -o BatchMode=yes "${COMMON[@]}" "$CMD"
fi

if [ -n "${VPS_SSH_PASSWORD:-}" ]; then
    if ! command -v sshpass >/dev/null 2>&1; then
        if command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
            sudo apt-get update -qq >/dev/null 2>&1
            sudo apt-get install -y -qq sshpass >/dev/null 2>&1
        else
            apt-get update -qq >/dev/null 2>&1
            apt-get install -y -qq sshpass >/dev/null 2>&1
        fi
    fi
    command -v sshpass >/dev/null 2>&1 || { echo "sshpass tidak bisa dipasang" >&2; exit 3; }
    exec sshpass -p "$VPS_SSH_PASSWORD" ssh \
        -o PreferredAuthentications=password -o PubkeyAuthentication=no \
        -o NumberOfPasswordPrompts=1 "${COMMON[@]}" "$CMD"
fi

echo "Tidak ada VPS_SSH_KEY atau VPS_SSH_PASSWORD pada environment." >&2
exit 4
