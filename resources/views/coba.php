 <!-- db ai logs -->
 Schema::create('ai_logs', function (Blueprint $table) {
 $table->id();

 $table->foreignId('user_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->string('role');

 $table->longText('question');

 $table->longText('answer');

 $table->string('action')->nullable();

 $table->string('source');

 $table->timestamps();
 });

 <!-- db ai rekomendation -->
 Schema::create('ai_recommendations', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->text('recommendation');

 $table->text('reason');

 $table->enum('status', [
 'pending',
 'accepted',
 'ignored'
 ])->default('pending');

 $table->timestamps();
 });

 <!-- ini db ai cache -->
 Schema::create('ai_cache', function (Blueprint $table) {
 $table->id();

 $table->string('cache_key')
 ->unique();

 $table->longText('question');

 $table->longText('answer');

 $table->string('source');

 $table->timestamp('expired_at')
 ->nullable();

 $table->timestamps();
 });

 <!-- ini db ai task -->
 Schema::create('ai_tasks', function (Blueprint $table) {
 $table->id();

 $table->foreignId('user_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->string('task_name');

 $table->longText('instruction');

 $table->enum('status', [
 'pending',
 'running',
 'completed',
 'failed'
 ]);

 $table->timestamps();
 });


 <!-- ini db ai action log -->
 Schema::create('ai_action_logs', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ai_log_id')
 ->nullable()
 ->constrained('ai_logs')
 ->nullOnDelete();

 $table->string('action_type');

 $table->longText('action_data');

 $table->enum('result', [
 'success',
 'failed',
 'blocked'
 ]);

 $table->timestamps();
 });

 <!-- ini db users -->
 Schema::create('users', function (Blueprint $table) {
 $table->id();

 $table->foreignId('role_id')->constrained()->cascadeOnDelete();

 $table->string('name');

 $table->string('username')->unique();

 $table->string('email')->nullable()->unique();

 $table->string('nim')->nullable()->unique();

 $table->string('nip')->nullable()->unique();

 $table->string('no_hp');

 $table->string('password');

 $table->string('security_question')->nullable();

 $table->string('security_answer')->nullable();

 $table->string('qr_code')->nullable();

 $table->string('qr_url')->nullable();

 $table->string('avatar')->nullable();

 $table->timestamp('last_login_at')->nullable();

 $table->timestamp('last_password_changed_at')->nullable();

 $table->rememberToken();

 $table->timestamps();

 $table->softDeletes();
 });

 <!-- ini db roles -->

 Schema::create('roles', function (Blueprint $table) {
 $table->id();

 $table->string('name')->unique();
 $table->string('description')->nullable();
 $table->boolean('is_active')
 ->default(true);
 $table->timestamps();
 $table->softDeletes();
 });

 <!-- ini db software -->
 Schema::create('software', function (Blueprint $table) {
 $table->id();

 $table->string('name');

 $table->string('developer')->nullable();

 $table->string('version')->nullable();

 $table->text('description')->nullable();

 $table->unsignedInteger('estimated_minutes')->default(30);

 $table->timestamps();

 $table->softDeletes();
 });

 <!-- ini db tickets -->
 Schema::create('tickets', function (Blueprint $table) {
 $table->id();

 $table->string('ticket_number')->unique();

 $table->uuid('qr_token')->unique();

 $table->string('qr_code')->nullable();

 $table->enum('type', [
 'installation',
 'repair'
 ]);

 $table->foreignId('user_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->enum('status', [
 'waiting',
 'diagnosis',
 'processing',
 'testing',
 'completed',
 'failed',
 'cancelled'
 ])->default('waiting');

 $table->enum('priority', [
 'normal',
 'high',
 'urgent'
 ])->default('normal');

 $table->timestamp('estimated_finish')->nullable();
 $table->timestamp('completed_at')->nullable();

 $table->timestamp('wa_notification_sent_at')->nullable();
 $table->timestamp('email_notification_sent_at')->nullable();

 $table->boolean('is_public')->default(true);

 $table->date('booking_date')->nullable();
 $table->enum('session', ['morning', 'afternoon'])->nullable();
 $table->unsignedInteger('queue_number')->nullable();
 $table->timestamp('scheduled_start')->nullable();
 $table->timestamp('scheduled_end')->nullable();

 $table->timestamps();

 $table->softDeletes();
 });

 <!-- ini db penginstalans -->
 {
 Schema::create('penginstalans', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->foreignId('user_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->foreignId('software_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->enum('installation_result', [
 'success',
 'failed'
 ])->nullable();

 $table->text('note')->nullable();

 $table->timestamps();

 $table->softDeletes();
 });
 }

 <!-- ini db perbaikan -->
 Schema::create('perbaikans', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->foreignId('user_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->string('item_name');

 $table->string('item_location')->nullable();

 $table->text('damage_description');

 $table->text('repair_action')->nullable();

 $table->enum('repair_result', [
 'success',
 'failed',
 'unrepairable'
 ])->nullable();

 $table->text('note')->nullable();

 $table->timestamps();

 $table->softDeletes();
 });

 <!-- ini db ticket status log -->
 Schema::create('ticket_status_logs', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->string('old_status')->nullable();

 $table->string('new_status');

 $table->text('note')->nullable();

 $table->foreignId('changed_by')
 ->nullable()
 ->constrained('users')
 ->nullOnDelete();

 $table->timestamps();
 });

 <!-- ini db ticket comments -->
 Schema::create('ticket_comments', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->foreignId('user_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->text('comment');

 $table->boolean('is_internal')
 ->default(true);

 $table->timestamps();
 });

 <!-- ini db notifications -->
 Schema::create('notifications', function (Blueprint $table) {
 $table->id();

 $table->foreignId('user_id')
 ->constrained()
 ->cascadeOnDelete();

 $table->foreignId('ticket_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->enum('type', [
 'system',
 'whatsapp',
 'ai'
 ]);

 $table->string('title');

 $table->text('message');

 $table->boolean('is_read')
 ->default(false);

 $table->timestamps();
 });

 <!-- ini db trusted website (ai akses data dari db ini untuk cek url web yg diizinkan di akses) -->
 Schema::create('trusted_websites', function (Blueprint $table) {
 $table->id();

 $table->string('name');

 $table->string('url');

 $table->text('description')->nullable();

 $table->boolean('is_active')
 ->default(true);

 $table->timestamps();
 });

 <!-- ini db login logs -->
 Schema::create('login_logs', function (Blueprint $table) {
 $table->id();

 $table->foreignId('user_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->ipAddress('ip_address')
 ->nullable();

 $table->text('user_agent')
 ->nullable();

 $table->enum('status', [
 'online',
 'offline'
 ]);

 $table->decimal('latitude', 10, 8)->nullable();
 $table->decimal('longitude', 11, 8)->nullable();
 $table->decimal('accuracy_m', 10, 2)->nullable();
 $table->decimal('distance_from_anchor_m', 10, 2)->nullable();
 $table->string('location_status')->nullable(); // inside, outside, unknown

 $table->timestamp('login_at')
 ->nullable();

 $table->timestamp('logout_at')
 ->nullable();

 $table->timestamps();
 });

 <!-- ini db user activities -->
 Schema::create('user_activities', function (Blueprint $table) {
 $table->id();

 $table->foreignId('user_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->string('module');

 $table->string('activity');

 $table->longText('description')
 ->nullable();

 $table->json('old_data')
 ->nullable();

 $table->json('new_data')
 ->nullable();

 $table->timestamps();
 });

 <!-- ini db maintenance -->
 Schema::create('maintenances', function (Blueprint $table) {
 $table->id();

 $table->boolean('is_active')
 ->default(false);

 $table->text('message')
 ->nullable();

 $table->timestamp('started_at')
 ->nullable();

 $table->timestamp('ended_at')
 ->nullable();

 $table->timestamps();
 });

 <!-- ini db system setting untuk setting website (mengatur mode tertentu) -->
 Schema::create('system_settings', function (Blueprint $table) {
 $table->id();

 $table->string('key')
 ->unique();

 $table->longText('value')
 ->nullable();

 $table->text('description')
 ->nullable();

 $table->timestamps();
 });

 <!-- ini db rekaps -->
 Schema::create('rekaps', function (Blueprint $table) {
 $table->id();

 $table->date('rekap_date')->unique();

 $table->unsignedInteger('total_installations')->default(0);
 $table->unsignedInteger('total_repairs')->default(0);

 $table->unsignedInteger('completed_tickets')->default(0);
 $table->unsignedInteger('failed_tickets')->default(0);
 $table->unsignedInteger('pending_tickets')->default(0);

 $table->timestamps();
 });

 <!-- ini db vercel sync (untuk menyimpan data sync terakhir ke vercel) -->
 Schema::create('vercel_sync_logs', function (Blueprint $table) {
 $table->id();

 $table->foreignId('ticket_id')
 ->nullable()
 ->constrained()
 ->nullOnDelete();

 $table->enum('sync_status', [
 'pending',
 'success',
 'failed'
 ]);

 $table->longText('response')
 ->nullable();

 $table->timestamp('synced_at')
 ->nullable();

 $table->timestamps();
 });

 