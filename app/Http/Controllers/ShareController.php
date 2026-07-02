<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * Requirement #12: forward a post to a social media platform of choice.
     * No platform API keys are required for this flow -- it opens each
     * network's standard share-intent URL (the same mechanism used by
     * "share" buttons across the web), pre-filled with a link back to the
     * post and an excerpt of its content.
     */
    public function share(Request $request, Post $post)
    {
        $request->validate([
            'platform' => 'required|in:twitter,facebook,whatsapp,linkedin',
        ]);

        $url = route('topics.show', $post->topic_id).'#post-'.$post->id;
        $text = str($post->body)->limit(150).' - via Smart Discussion Forum';

        $shareUrl = match ($request->platform) {
            'twitter' => 'https://twitter.com/intent/tweet?text='.urlencode($text).'&url='.urlencode($url),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($url),
            'whatsapp' => 'https://wa.me/?text='.urlencode($text.' '.$url),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url='.urlencode($url),
        };

        return redirect()->away($shareUrl);
    }
}
