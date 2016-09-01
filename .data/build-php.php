<?php

namespace at\labs;

use RuntimeException;

function buildComposerJson($pwd, $projects)
{
    $json = json_decode(file_get_contents("$pwd/php/composer.json"), true);
    foreach (array_keys($projects['php']) as $service) {
        $json['autoload']['psr-4']["go1\\$service\\"] = "/app/$service/";
        if (file_exists("$pwd/php/$service/composer.json")) {
            $sub = json_decode(file_get_contents("$pwd/php/{$service}/composer.json"), true);
            if (!empty($sub['require'])) {
                foreach ($sub['require'] as $lib => $version) {
                    $json['require'][$lib] = $version;
                }
            }
        }

        passthru("mkdir -p $pwd/php/$service/vendor");
        file_put_contents(
            "$pwd/php/$service/vendor/autoload.php",
            '<?php return if (is_file("/app/vendor/autoload.php")) require_once "/app/vendor/autoload.php";'
        );
    }

    ksort($json['autoload']['psr-4']);
    ksort($json['require']);
    $json = json_encode($json, JSON_PRETTY_PRINT);
    $json = str_replace('\/', '/', $json);
    file_put_contents("$pwd/php/composer.json", $json);
}

return function ($pwd, $home, $projects) {
    $docker = "docker run --rm";
    $docker .= " -v $pwd/php/:/app/";
    $docker .= " -v $pwd/.data/cli/:/cli/";
    $docker .= " -v '$home/.ssh/id_rsa:/root/.ssh/id_rsa'";
    $docker .= " -v $home/.composer/:/root/.composer/";
    $docker .= " -w=/app/ go1com/php:php7";

    buildComposerJson($pwd, $projects);

    if (!is_file("$pwd/.data/cli/composer.phar")) {
        copy('https://getcomposer.org/installer', "$pwd/.data/cli/composer-setup.php");
        if (hash_file('SHA384', "$pwd/.data/cli/composer-setup.php") !== 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') {
            unlink("$pwd/.data/cli/composer-setup.php");
            throw new RuntimeException('[COMPOSER] Installer corrupt');
        }

        passthru("cd $pwd/.data/cli && php composer-setup.php");
    }

    passthru("$docker sh /app/install.sh");
};