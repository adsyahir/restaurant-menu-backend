# Jenkins image with the tools the pipelines need: docker CLI (talks to the
# host daemon via the mounted socket), kubectl, and envsubst.
# `k3s` (for `k3s ctr images import`) is bind-mounted from the host at run time.
FROM jenkins/jenkins:lts-jdk17

USER root

# docker CLI only (no daemon) — static binary, most reliable across bases.
ARG DOCKER_VERSION=27.3.1
RUN apt-get update \
 && apt-get install -y --no-install-recommends gettext-base curl ca-certificates \
 && curl -fsSL "https://download.docker.com/linux/static/stable/x86_64/docker-${DOCKER_VERSION}.tgz" \
      | tar -xz --strip-components=1 -C /usr/local/bin docker/docker \
 && curl -fsSL -o /usr/local/bin/kubectl \
      "https://dl.k8s.io/release/$(curl -fsSL https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl" \
 && chmod +x /usr/local/bin/docker /usr/local/bin/kubectl \
 && rm -rf /var/lib/apt/lists/*

# Stay root so the pipeline can reach the docker + containerd sockets and the
# k3s kubeconfig. (Single-server self-hosted CI — acceptable here.)
