<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Public-path asset URL that stays on the current host and encodes path segments.
     */
    public static function publicUrl(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $encoded = collect(explode('/', $relativePath))
            ->filter(fn ($segment) => $segment !== '')
            ->map(fn ($segment) => rawurlencode($segment))
            ->implode('/');

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return asset($encoded);
        }

        return rtrim(request()->getBasePath(), '/').'/'.$encoded;
    }

    /**
     * Get the URL for a storage image with fallback
     */
    public static function getStorageImageUrl($imagePath, $fallbackPath = 'frontend/assets/img/lazy-placeholder.png')
    {
        if (!$imagePath) {
            return self::publicUrl($fallbackPath);
        }

        $fullPath = public_path('storage/' . $imagePath);
        
        if (file_exists($fullPath)) {
            return self::publicUrl('storage/' . $imagePath);
        }

        return self::publicUrl($fallbackPath);
    }

    /**
     * Get profile image URL with fallback
     */
    public static function getProfileImageUrl($profile, $fallbackPath = 'frontend/assets/img/avatar.jpg')
    {
        if (!$profile || !$profile->photo) {
            return self::publicUrl($fallbackPath);
        }

        return self::getStorageImageUrl($profile->photo, $fallbackPath);
    }

    /**
     * Resolve a professional profile photo URL, or null when missing.
     * Prefer host-relative paths so images work under artisan serve and reverse proxies.
     */
    public static function professionalPhotoUrl(?string $photoPath): ?string
    {
        $blocked = [
            'professional_documents/McPqTOLmpLHhukE6ka0DJmmNWTU3qG6segI57ccy.jpg',
            'uploads/professionals/photo/media_68b52ad01436a.jpg',
        ];

        $path = ltrim(str_replace('\\', '/', (string) $photoPath), '/');
        if ($path === '' || in_array($path, $blocked, true)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (is_file(public_path('storage/'.$path))) {
            return self::publicUrl('storage/'.$path);
        }

        if (is_file(public_path($path))) {
            return self::publicUrl($path);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return self::publicUrl('storage/'.$path);
        }

        return null;
    }

    /**
     * Resolve a customer/user avatar that never points at a missing file.
     */
    public static function customerAvatarUrl(?object $user, ?object $profile = null, string $fallbackPath = 'frontend/assets/img/avatar.jpg'): string
    {
        $candidates = [];
        $image = is_object($profile) ? ($profile->profile_image ?? null) : null;
        if (is_string($image) && trim($image) !== '') {
            $candidates[] = ltrim(str_replace('\\', '/', $image), '/');
            $candidates[] = ltrim(str_replace('\\', '/', preg_replace('#^storage/#', '', $image)), '/');
        }
        $legacy = is_object($user) ? ($user->profile_photo_path ?? null) : null;
        if (is_string($legacy) && trim($legacy) !== '') {
            $candidates[] = 'uploads/user_images/'.ltrim($legacy, '/');
        }

        foreach (array_unique($candidates) as $relative) {
            if ($relative !== '' && file_exists(public_path($relative))) {
                return self::publicUrl($relative);
            }
        }

        return self::publicUrl($fallbackPath);
    }

    /**
     * Get gallery image URL with fallback
     */
    public static function getGalleryImageUrl($imagePath, $fallbackPath = 'frontend/assets/img/lazy-placeholder.png')
    {
        // Handle images that might already have 'storage/' prefix
        $cleanPath = str_replace('storage/', '', $imagePath);
        return self::getStorageImageUrl($cleanPath, $fallbackPath);
    }
}