<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application.booklore_url', null);

        $this->migrator->add('application.booklore_username', null);

        $this->migrator->add('application.booklore_library_id', null);

        $this->migrator->addEncrypted('application.booklore_access_token', null);
        $this->migrator->addEncrypted('application.booklore_refresh_token', null);
        $this->migrator->add('application.booklore_access_token_expires_at', null);
    }
};
