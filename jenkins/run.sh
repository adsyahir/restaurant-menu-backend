#!/bin/sh
# Build + run Jenkins as a container on the k3s server, wired to the host's
# docker daemon, k3s containerd, and kube API.
#
#   --network host      -> kubeconfig's 127.0.0.1:6443 reaches the k3s API,
#                          and Jenkins UI is on the host's :8080
#   docker.sock         -> `docker build` runs on the host daemon
#   containerd.sock     -> `k3s ctr images import` reaches k3s' image store
#   /usr/local/bin/k3s  -> the `k3s ctr` binary (matches the host)
#   k3s.yaml            -> kubectl credentials
#   --user root         -> access those root-owned sockets
set -e

docker build -t jenkins-k3s "$(dirname "$0")"

docker rm -f jenkins 2>/dev/null || true
docker run -d --name jenkins --restart unless-stopped \
  --network host \
  --user root \
  -v jenkins_home:/var/jenkins_home \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v /run/k3s/containerd/containerd.sock:/run/k3s/containerd/containerd.sock \
  -v /usr/local/bin/k3s:/usr/local/bin/k3s:ro \
  -v /etc/rancher/k3s/k3s.yaml:/var/jenkins_home/.kube/config:ro \
  -e KUBECONFIG=/var/jenkins_home/.kube/config \
  jenkins-k3s

echo "Jenkins starting on http://<server-ip>:8080"
echo "Admin password:"
sleep 5
docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword 2>/dev/null || \
  echo "  (wait a few seconds, then: docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword)"
