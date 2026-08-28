<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Who changed what, in the admin. */
final class AuditService
{
    public static function record(string $action, ?string $entity = null, ?int $entityId = null, mixed $changes = null): void
    {
        $user = AuthService::user();

        Database::insert('admin_audit_logs', [
            'user_id'    => $user === null ? null : (int) $user['id'],
            'user_email' => $user['email'] ?? null,
            'action'     => mb_substr($action, 0, 80),
            'entity'     => $entity === null ? null : mb_substr($entity, 0, 80),
            'entity_id'  => $entityId,
            'changes'    => $changes === null ? null : json_encode($changes, JSON_UNESCAPED_UNICODE),
            'source_ip'  => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    public static function recent(int $limit = 50): array
    {
        return Database::select('SELECT * FROM admin_audit_logs ORDER BY created_at DESC LIMIT ' . max(1, min(500, $limit)));
    }
}
