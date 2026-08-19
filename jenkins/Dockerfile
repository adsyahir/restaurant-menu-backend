# Jenkins image with the tools the pipelines need: docker CLI (builds on the
# host daemon via the mounted socket), kubectl, and envsubst.
# `k3s` (for `k3s ctr images import`) is NOT baked in — it's bind-mounted from
# the host at run time (see run.sh), so it matches the host's k3s exactly.
FROM jenkins/jenkins:lts-jdk17

USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends docker.io gettext-base curl ca-certificates \
 && curl -fsSL -o /usr/local/bin/kubectl \
      "https://dl.k8s.io/release/$(curl -fsSL https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl" \
 && chmod +x /usr/local/bin/kubectl \
 && rm -rf /var/lib/apt/lists/*
# Stay root so the pipeline can reach the docker + containerd sockets and the
# k3s kubeconfig. (Single-server self-hosted CI — acceptable here.)
