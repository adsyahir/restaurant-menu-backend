// Backend CI/CD — runs ON the k3s server. No registry: build the image and
// import it straight into k3s' containerd, then apply the manifests.
//
// Flow: test -> build -> import -> render config/secret from .env -> apply -> roll.
//
// Jenkins prerequisites (see the setup notes):
//   - the agent has: docker, kubectl, envsubst, and passwordless `sudo k3s`
//   - KUBECONFIG points at /etc/rancher/k3s/k3s.yaml (readable by the jenkins user)
//   - a "Secret file" credential 'rms-backend-env' = the real backend .env
pipeline {
  agent any

  triggers { githubPush() }          // auto-run on push (needs a GitHub webhook)

  options {
    timestamps()
    disableConcurrentBuilds()
    timeout(time: 30, unit: 'MINUTES')
  }

  environment {
    NS    = 'rms'
    IMAGE = 'restaurant-menu-backend'
  }

  stages {
    stage('Checkout') {
      steps { checkout scm }
    }

    stage('Test') {
      steps {
        sh '''
          docker run --rm -v "$PWD":/app -w /app \
            -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
            composer:2 sh -c '
              composer install --no-interaction --prefer-dist --no-progress
              cp -n .env.example .env || true
              php artisan key:generate
              vendor/bin/pint --test
              php artisan test --compact
            '
        '''
      }
    }

    stage('Build + import') {
      steps {
        sh '''
          docker build -t $IMAGE:latest .
          docker save $IMAGE:latest | sudo k3s ctr images import -
        '''
      }
    }

    stage('Deploy') {
      steps {
        withCredentials([file(credentialsId: 'rms-backend-env', variable: 'ENVFILE')]) {
          sh '''
            cp "$ENVFILE" k8s/.env.deploy
            set -a; . k8s/.env.deploy; set +a

            # config + secret rendered from the .env
            envsubst < k8s/configmap.yaml | kubectl apply -n $NS -f -
            envsubst < k8s/secret.yaml    | kubectl apply -n $NS -f -

            # postgres + deployment + service
            kubectl apply -k k8s/

            # ingress host from APP_URL (scheme stripped)
            APP_URL=$(printf '%s' "$APP_URL" | sed -E 's#^https?://##; s#/.*##')
            APP_URL="$APP_URL" envsubst '$APP_URL' < k8s/ingress.yaml | kubectl apply -n $NS -f -

            # pick up the re-imported image + new env
            kubectl -n $NS rollout restart deployment/rms-backend
            kubectl -n $NS rollout status  deployment/rms-backend --timeout=180s

            rm -f k8s/.env.deploy
          '''
        }
      }
    }
  }

  post {
    always  { sh 'rm -f k8s/.env.deploy || true' }
    success { echo 'Backend deployed.' }
    failure { echo 'Backend pipeline failed — see stage logs.' }
  }
}
