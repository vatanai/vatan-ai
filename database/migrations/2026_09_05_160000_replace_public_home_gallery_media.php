<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $paths = [
            '0LIJ2V9Gt4R9yrc2ghLdsKblqVYZMINVHMA4Emtm.jpg', '3gIHeQGfpaMLONqBTtEGkefJJBquG48CFje4P4eC.jpg',
            '5x7ObuoJQamxHQjpCIQbMSzohPPt2mUdLFhXEXot.jpg', '6TDgpG2lLFAfjNL8AkUph1KfFq3wWuOlBfNaDp2Q.jpg',
            '8oRaf78DiUhpT6TW247n0aob0CUGR5jD5gMZVLNm.jpg', 'Divxhe66Jf3pvI3ZPsWBboUdttCtTubA8jZoD6D4.jpg',
            'EEykfVC6hN9tDMlPkvBdzO4XO4rrlmS7CZk4AM27.jpg', 'FWpFnc3jvVZtEpbKWuLJO8zKtlHjVvgsikVD5q9U.jpg',
            'GVB0WCPpY3RJkIbtv85q4sFFgA42iZdDR61L5eA0.jpg', 'HazsYmX1oapvIbmDT1JHPv1y1NeXYwfwgBHom4QY.jpg',
            'IdmhPugccgizmFtDSTwhYbaLoyRkUX5v1fcNyejX.jpg', 'MMrqI2J5yjKDiJcrKWVOsl064lAHepndF7YJ9nLp.jpg',
            'MR23WWI83ALyLBsSCiKiNRCWXFtvZUq47juYlJ6z.jpg', 'NnmPpT5LOC4AgkrYSU6jVrpMq37PxDDtUiqEiMWg.jpg',
            'aBVGVe5ctvNl1e4Kym7CCjCoZty2ntikyy4vm8fx.jpg', 'g91HxoZRvc95l3YRtMRl7d7MRVOwo92haFPN8eqh.jpg',
            'jmcpZTtWzvyLXI6vIcKVBLupQnOj6oz35CrGtdo9.jpg', 'jvT1acdMcMR7laK4zJhfsZSyjQ9N3NidgSFTG8WA.jpg',
            'l5zRsUOpqBinsedrbxrTei47M4XpeNWX3tWHtjyN.jpg', 'tCxwqS3ArqSpyjihtBySizLvDcNkF4OZlOUeUe9b.jpg',
            'tYEc0WL7jr3ur79eU76jUJ5AUonhN0z3yjuPuJCH.jpg', 'vQy5UKiXytfiFEMo7ydXxeZIQb816FCHbqaanJSj.jpg',
            'yPYqWObyhJxDVQYuxlmbjfAuaeNx83tvPLexG5Rc.jpg',
        ];
        $items = array_map(fn (string $path): array => ['type' => 'asset', 'path' => 'assets/img/home-site/' . $path, 'title' => 'نمونه وطن'], $paths);
        foreach (['hero-gallery' => array_slice($items, 0, 12), 'ideas-gallery' => array_slice($items, 12, 5), 'inspiration-gallery' => array_slice($items, 17)] as $key => $galleryItems) {
            DB::table('home_page_galleries')->where('key', $key)->update([
                'items' => json_encode($galleryItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // رسانه‌های قبلی در migration قبلی نگهداری شده‌اند؛ بازگردانی خودکار آن‌ها امن نیست.
    }
};
