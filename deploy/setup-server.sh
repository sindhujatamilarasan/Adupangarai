#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu 24.04 server. Run as root:
#   curl -fsSL https://raw.githubusercontent.com/sindhujatamilarasan/Adupangarai/main/deploy/setup-server.sh | bash
set -euo pipefail

echo "==> Updates, firewall, automatic security updates"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q && apt-get upgrade -yq
apt-get install -yq git ufw fail2ban unattended-upgrades curl
dpkg-reconfigure -f noninteractive unattended-upgrades
ufw default deny incoming && ufw default allow outgoing
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw allow 443/udp
ufw --force enable
systemctl enable --now fail2ban

echo "==> Docker"
command -v docker >/dev/null || curl -fsSL https://get.docker.com | sh

echo "==> Swap (helps small servers during builds)"
if ! swapon --show | grep -q .; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> SSH: keys only (if a key is installed)"
if [ -s /root/.ssh/authorized_keys ]; then
  printf 'PasswordAuthentication no\nPermitRootLogin prohibit-password\n' > /etc/ssh/sshd_config.d/10-adupangarai.conf
  systemctl reload ssh || systemctl reload sshd || true
else
  echo "   (no SSH key yet: password login left on. Add a key, then re-run this script.)"
fi

echo "==> App code in /opt/adupangarai"
[ -d /opt/adupangarai/.git ] || git clone https://github.com/sindhujatamilarasan/Adupangarai.git /opt/adupangarai
mkdir -p /opt/adupangarai/backups /opt/adupangarai/downloads

echo
echo "Done. Next: fill /opt/adupangarai/.env and /opt/adupangarai/api/.env (see deploy/README.md), then run deploy/deploy.sh --first"
