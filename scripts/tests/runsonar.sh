#!/bin/bash

RUNREVPARSE=false
REPO=$GITHUB_REPOSITORY
BRANCH=${GITHUB_HEAD_REF:-GITHUB_REF}
BRANCH=${BRANCH##refs/heads/}
#PR_NUMBER=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
BUILD_NUMBER=$RUNNER_TRACKING_ID
GIT_COMMIT=$GITHUB_SHA

echo "REPO=$REPO"
echo "BRANCH=$BRANCH"
#echo "PR_NUMBER=$PR_NUMBER"
echo "BUILD_NUMBER=$BUILD_NUMBER"
echo "GIT_COMMIT=$GIT_COMMIT"

set -euo pipefail

REPOSITORY_OWNER=asbiin/laravel-webauthn
SONAR_ORGANIZATION=asbiin-github

function installSonar {
  echo '== Setup sonar scanner'

  # set version of sonar scanner to use :
  sonarversion=${SONAR_VERSION:-}
  if [ -z "${sonarversion:-}" ]; then
    sonarversion=3.2.0.1227
  fi
  echo "== Using sonarscanner $sonarversion"

  mkdir -p $HOME/sonarscanner
  pushd $HOME/sonarscanner > /dev/null
  if [ ! -d "sonar-scanner-$sonarversion" ]; then
    echo "== Downloading sonarscanner $sonarversion"
    java_path=$(which java || true)
    if [ -x "$java_path" ]; then
      wget --quiet --continue https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-$sonarversion.zip
      unzip -q sonar-scanner-cli-$sonarversion.zip
      rm sonar-scanner-cli-$sonarversion.zip
    else
      wget --quiet --continue https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-$sonarversion-linux.zip
      unzip -q sonar-scanner-cli-$sonarversion-linux.zip
      rm sonar-scanner-cli-$sonarversion-linux.zip
      mv sonar-scanner-$sonarversion-linux sonar-scanner-$sonarversion
    fi
  fi
  export SONAR_SCANNER_HOME=$HOME/sonarscanner/sonar-scanner-$sonarversion
  export PATH=$SONAR_SCANNER_HOME/bin:$PATH
  popd > /dev/null
}

function CommonParams {
  extra=""
  if [ "$REPO" != "$REPOSITORY_OWNER" ]; then
    # Avoid forks to send reports to the same project
    project="${REPO/\//_}"
    extra="$extra -Dsonar.projectKey=asbiin_laravel-webauthn:$project -Dsonar.projectName=$project"
  fi

  echo -Dsonar.host.url=$SONAR_HOST_URL \
       -Dsonar.organization=$SONAR_ORGANIZATION \
       -Dsonar.php.tests.reportPath=$SONAR_RESULT \
       -Dsonar.php.coverage.reportPaths=$SONAR_COVERAGE \
       -Dsonar.analysis.buildNumber=$BUILD_NUMBER \
       -Dsonar.analysis.pipeline=$BUILD_NUMBER \
       -Dsonar.analysis.sha1=$GIT_COMMIT \
       -Dsonar.analysis.repository=$REPO \
       $extra
}

function gitFetch {
  echo '== gitFetch'
  echo '# git fetch --all'
  git fetch --all
  if [ "$RUNREVPARSE" == "true" ]; then
    if [ -n "${PULL_REQUEST_BASEBRANCH:-}" ]; then
      echo "# git branch -D $PULL_REQUEST_BASEBRANCH"
      git branch -D $PULL_REQUEST_BASEBRANCH
      echo "# git rev-parse origin/$PULL_REQUEST_BASEBRANCH"
      git rev-parse origin/$PULL_REQUEST_BASEBRANCH
    fi
  fi
  echo ''
}

if [ -z "${SONAR_HOST_URL:-}" ]; then
  export SONAR_HOST_URL=https://sonarcloud.io
fi

if [ "$BRANCH" == "master" ] && [ "$PR_NUMBER" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '=========================='
  echo '== SONAR:Analyze master =='
  echo '=========================='
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=master"

  echo "# sonar-scanner $SONAR_PARAMS"
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN
  exit $?

elif [ -n "${BRANCH:-}" ] && [ "$PR_NUMBER" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '=================================='
  echo '== SONAR:Analyze release branch =='
  echo '=================================='
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=$(git describe --abbrev=0 --tags --exact-match ${GIT_COMMIT} 2>/dev/null >/dev/null)"

  echo "# sonar-scanner $SONAR_PARAMS"
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN
  exit $?

elif [ "$PR_NUMBER" != "false" ] && [ -n "${SONAR_TOKEN:-}" ] && [ -n "${GITHUB_TOKEN:-}" ]; then

  REPOS_VALUES=($(curl -H "Authorization: token $GITHUB_TOKEN" -sSL https://api.github.com/repos/$REPO/pulls/$PR_NUMBER | jq -r -c ".head.repo.full_name, .head.repo.owner.login, .base.ref, .head.ref"))

  PULL_REQUEST_BRANCH=
  PULL_REQUEST_REPOSITORY=$(jq --raw-output .repository.full_name "$GITHUB_EVENT_PATH")
  PULL_REQUEST_USER=$(jq --raw-output .repository.owner.login "$GITHUB_EVENT_PATH")
  PULL_REQUEST_BASEBRANCH=$(jq --raw-output .base_ref "$GITHUB_EVENT_PATH")
  PULL_REQUEST_HEADBRANCH=$(jq --raw-output .ref "$GITHUB_EVENT_PATH")

fork=$(jq --raw-output .repository.fork "$GITHUB_EVENT_PATH")


  if [ -z "${PULL_REQUEST_REPOSITORY:-}" ] || [ "$PULL_REQUEST_REPOSITORY" == "null" ]; then
    echo '== Error with github api call'
    exit 11
  elif [ "$PULL_REQUEST_REPOSITORY" == "$REPOSITORY_OWNER" ]; then
    echo '========================================='
    echo '== SONAR:Analyze internal pull request =='
    echo '========================================='
    PULL_REQUEST_BRANCH=$PULL_REQUEST_HEADBRANCH
  else
    echo '========================================='
    echo '== SONAR:Analyze external pull request =='
    echo '========================================='
    echo "== External repository: $PULL_REQUEST_REPOSITORY"
    PULL_REQUEST_BRANCH="${PULL_REQUEST_USER}__$PULL_REQUEST_HEADBRANCH"

PULL_REQUEST_BASEBRANCH=$GITHUB_BASE_REF
PULL_REQUEST_HEADBRANCH=$GITHUB_HEAD_REF

  fi
  echo "PULL_REQUEST_BRANCH=$PULL_REQUEST_BRANCH"
  echo "PULL_REQUEST_REPOSITORY=$PULL_REQUEST_REPOSITORY"
  echo "PULL_REQUEST_USER=$PULL_REQUEST_USER"
  echo "PULL_REQUEST_BASEBRANCH=$PULL_REQUEST_BASEBRANCH"
  echo "PULL_REQUEST_HEADBRANCH=$PULL_REQUEST_HEADBRANCH"

  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.pullrequest.key=$PR_NUMBER \
    -Dsonar.pullrequest.base=$PULL_REQUEST_BASEBRANCH \
    -Dsonar.pullrequest.branch=$PULL_REQUEST_BRANCH \
    -Dsonar.pullrequest.provider=GitHub \
    -Dsonar.pullrequest.github.repository=$REPO"

  echo "# sonar-scanner $SONAR_PARAMS"
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN
  exit $?

else
  echo '======================'
  echo '== SONAR:No analyze =='
  echo '======================'

fi
