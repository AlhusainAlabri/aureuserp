<?php

namespace App\Console\Commands;

use App\Services\Deploy\ApplicationUpdateDeployer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('github:deploy {--yes : Apply the GitHub update} {--user-id= : User id that started the deploy}')]
#[Description('Pull a fast-forward GitHub update and run the production deploy steps')]
class DeployFromGitHub extends Command
{
    public function handle(ApplicationUpdateDeployer $deployer): int
    {
        if (! $this->option('yes')) {
            $this->error(__('github-deploy.confirm_required'));

            return self::FAILURE;
        }

        $userId = $this->option('user-id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        try {
            $deployer->deploy($userId);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(__('github-deploy.completed'));

        return self::SUCCESS;
    }
}
