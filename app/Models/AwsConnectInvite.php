<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Undangan untuk satu klien menghubungkan akun AWS-nya lewat AssumeRole.
 *
 * Klien membuka link publik, membuat role yang mempercayai `trusted_account_id`
 * dengan `external_id` milik undangan ini, lalu menempelkan Role ARN-nya. Setelah
 * terverifikasi, undangan terpakai dan menunjuk ke akun yang terbentuk.
 */
class AwsConnectInvite extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    /** Nama role yang disarankan — policy sts:AssumeRole akun sumber dibatasi ke nama ini. */
    public const ROLE_NAME = 'GrabMaps-Usage-ReadOnly';

    protected $fillable = [
        'token', 'label', 'company_id', 'source_account_id', 'trusted_account_id',
        'external_id', 'region', 'expires_at', 'used_at', 'used_ip', 'aws_account_id',
        'keys_found', 'usage_share_id', 'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'source_account_id');
    }

    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class);
    }

    /** Link dashboard pemakaian yang dibuat otomatis saat klien terhubung. */
    public function usageShare(): BelongsTo
    {
        return $this->belongsTo(ApiKeyUsageShare::class, 'usage_share_id');
    }

    /**
     * Buat undangan baru. `$trustedAccountId` harus berasal dari
     * sts:GetCallerIdentity akun sumber, bukan isian manual.
     */
    public static function issue(
        AwsAccount $source,
        string $trustedAccountId,
        string $label,
        ?int $companyId,
        string $region,
        int $days,
        ?int $createdBy,
    ): self {
        if (!$source->account_number) {
            $source->update(['account_number' => $trustedAccountId]);
        }

        return static::create([
            'token'              => static::generateToken(),
            'label'              => $label,
            'company_id'         => $companyId,
            'source_account_id'  => $source->id,
            'trusted_account_id' => $trustedAccountId,
            'external_id'        => static::generateExternalId(),
            'region'             => $region,
            'expires_at'         => now()->addDays($days),
            'created_by'         => $createdBy,
        ]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return !$this->isUsed() && $this->expires_at->isPast();
    }

    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    /** External ID unik per klien — mencegah klien lain memakai role yang sama lewat kita. */
    public static function generateExternalId(): string
    {
        return 'grabmaps-' . Str::lower(Str::random(24));
    }

    public function url(): string
    {
        return route('aws-connect.show', $this->token);
    }

    /** Policy izin yang dipasang klien di role-nya: hanya baca. */
    public static function permissionPolicy(): array
    {
        return [
            'Version'   => '2012-10-17',
            'Statement' => [
                [
                    'Sid'      => 'ReadLocationApiKeys',
                    'Effect'   => 'Allow',
                    'Action'   => ['geo:ListKeys', 'geo:DescribeKey'],
                    'Resource' => '*',
                ],
                [
                    // Data source tiap resource (Grab/Esri/HERE) = provider yang dipakai klien.
                    'Sid'      => 'ReadLocationResources',
                    'Effect'   => 'Allow',
                    'Action'   => ['geo:ListPlaceIndexes', 'geo:ListMaps', 'geo:ListRouteCalculators', 'geo:ListGeofenceCollections'],
                    'Resource' => '*',
                ],
                [
                    'Sid'      => 'ReadUsageMetrics',
                    'Effect'   => 'Allow',
                    'Action'   => ['cloudwatch:GetMetricData', 'cloudwatch:ListMetrics'],
                    'Resource' => '*',
                ],
                [
                    'Sid'      => 'ReadRouteMatrixBilling',
                    'Effect'   => 'Allow',
                    'Action'   => 'ce:GetCostAndUsage',
                    'Resource' => '*',
                ],
                [
                    // Status invoice resmi (terbit/belum, jatuh tempo) di halaman Tagihan.
                    'Sid'      => 'ReadInvoiceSummaries',
                    'Effect'   => 'Allow',
                    'Action'   => ['invoicing:ListInvoiceSummaries', 'invoicing:GetInvoicePDF'],
                    'Resource' => '*',
                ],
            ],
        ];
    }

    /** Trust policy role klien: hanya akun sumber, dan hanya dengan External ID ini. */
    public function trustPolicy(): array
    {
        return [
            'Version'   => '2012-10-17',
            'Statement' => [[
                'Effect'    => 'Allow',
                'Principal' => ['AWS' => "arn:aws:iam::{$this->trusted_account_id}:root"],
                'Action'    => 'sts:AssumeRole',
                'Condition' => ['StringEquals' => ['sts:ExternalId' => $this->external_id]],
            ]],
        ];
    }

    /** Policy yang dipasang di IAM user akun sumber supaya boleh meng-assume role klien. */
    public static function sourcePolicy(): array
    {
        return [
            'Version'   => '2012-10-17',
            'Statement' => [[
                'Effect'   => 'Allow',
                'Action'   => 'sts:AssumeRole',
                'Resource' => 'arn:aws:iam::*:role/' . self::ROLE_NAME,
            ]],
        ];
    }

    /** Template CloudFormation siap unggah: role + policy baca, sudah berisi akun & External ID. */
    public function cloudFormationTemplate(): string
    {
        $indent = fn (array $doc, int $spaces) => preg_replace(
            '/^/m',
            str_repeat(' ', $spaces),
            json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $trust  = $indent($this->trustPolicy(), 8);
        $policy = $indent(self::permissionPolicy(), 12);
        $role   = self::ROLE_NAME;

        return <<<YAML
AWSTemplateFormatVersion: '2010-09-09'
Description: Read-only access for GrabMaps usage reporting (AWS Location Service, CloudWatch, Cost Explorer).

Resources:
  GrabMapsUsageRole:
    Type: AWS::IAM::Role
    Properties:
      RoleName: {$role}
      MaxSessionDuration: 3600
      AssumeRolePolicyDocument:
{$trust}
      Policies:
        - PolicyName: {$role}
          PolicyDocument:
{$policy}

Outputs:
  RoleArn:
    Description: Paste this value into the GrabMaps connect page.
    Value: !GetAtt GrabMapsUsageRole.Arn

YAML;
    }
}
