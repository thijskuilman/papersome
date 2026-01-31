<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application.booklore_url');

        $this->migrator->add('application.booklore_username');

        $this->migrator->add('application.booklore_library_id');

        $this->migrator->add('application.booklore_path_id');

        $this->migrator->addEncrypted('application.booklore_access_token');

        $this->migrator->addEncrypted('application.booklore_refresh_token');

        $this->migrator->add('application.booklore_access_token_expires_at');
    }
};
