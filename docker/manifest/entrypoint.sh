#!/usr/bin/env bash

ssh-keygen -A

# handle error behavior ba
if [[ "$ERRORS" != "1" ]] ; then
  sed -i -e "s/error_reporting =.*=/error_reporting = E_ALL/g" /usr/etc/php.ini
  sed -i -e "s/display_errors =.*/display_errors = stdout/g" /usr/etc/php.ini
fi

if [[ -v NO_OPCACHE ]]; then
    sed -i -e "s/zend_extension=opcache.so/;zend_extension=opcache.so/g" /etc/php.d/zend-opcache.ini
fi

# assume DEPLOYMENT_ENV is only set in aws cloud, meaning task role is applied and ssm is authorized
if [[ -n "${DEPLOYMENT_ENV}" ]]; then

  # provide simple/fast way of investigation and manual interaction in containers via ssh, as FARGATE doesn't provide any (docker exec or similar)
  # TODO review for fast tweaks
  echo "configure ssh daemon with /app/${DEPLOYMENT_ENV}/ssh-key-pub"

  mkdir -p /root/.ssh
  aws ssm get-parameter --region "${AWS_REGION}" --name /app/${DEPLOYMENT_ENV}/ssh-key-pub --with-decryption --output text --query "Parameter.Value" > /root/.ssh/authorized_keys
  chmod 600 /root/.ssh/authorized_keys


  # take dot-env file approach for parameter handling readability purposes, see environment var scope and piped sub processes
  parameterPath=/app/${DEPLOYMENT_ENV}/env
  echo "write dot-env file from ssm parameters with path ${parameterPath}"

  dotEnvFile=.env
  # TODO remove existing .env before build - provide default values differently
  rm -f ${dotEnvFile} && touch ${dotEnvFile}

  aws ssm get-parameters-by-path --region "${AWS_REGION}" --path "${parameterPath}" --with-decryption --query 'Parameters[*].[Name, Value]' --output text | while read -r rawKey envValue; do
    envKey=${rawKey/$parameterPath\//}
    echo "reading ${rawKey}, exporting to dot-env file as ${envKey}"
    if [[ "$envValue" == *\ * ]]; then
        sanitizedEnvValue='"'${envValue}'"'
    elif [[ $envValue =~ \#+ ]]; then
        sanitizedEnvValue='"'${envValue}'"'
    else
        sanitizedEnvValue=${envValue}
    fi
    echo ${envKey}="${sanitizedEnvValue}" >> ${dotEnvFile}
  done

  chmod 755 ${dotEnvFile}

  export $(cat ${dotEnvFile} | xargs)

else
  echo "no aws region and deployment param detected - no sshd configured"
fi

ln -s /usr/etc/php.ini /etc/php.ini

php artisan migrate --force

# super simple process handling, esp restarting on a crash
/usr/bin/supervisord -n -c /etc/supervisord.conf
