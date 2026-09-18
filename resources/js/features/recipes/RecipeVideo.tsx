import React, { useState } from 'react';
import { Play } from 'lucide-react';
import { Photo } from '../../components/ui/Photo';

/**
 * The whole of a YouTube id and nothing else.
 *
 * The server already serves ids through App\Support\YouTubeVideoId, so this is
 * a second lock on the same door: the id is interpolated into an iframe src,
 * and only these eleven characters can go in, whatever arrives in the payload.
 */
const VIDEO_ID = /^[A-Za-z0-9_-]{11}$/;

/** What the player is allowed to do once it is running. */
const PLAYER_ALLOW = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

interface RecipeVideoProps {
    /** The bare eleven-character id. Anything else renders the photo instead. */
    videoId: string;
    /** The dish, for the play control's label and the player's title. */
    title: string;
    /** The recipe's own photo treatment: shown if YouTube has no thumbnail for this id. */
    photo: React.ReactNode;
}

/**
 * A recipe's video, as a facade.
 *
 * Until someone asks to watch, this is a still image with a play control over
 * it — no YouTube iframe, no player script, no cookies. The first click swaps
 * in the nocookie player, already playing. That click is also what makes the
 * autoplay stick: browsers grant it on the gesture that created the frame.
 *
 * The only thing this is the whole codebase's example of, so it stays in one
 * file: if the embed ever needs changing, everything to change is here.
 */
export const RecipeVideo: React.FC<RecipeVideoProps> = ({ videoId, title, photo }) => {
    const [playing, setPlaying] = useState(false);

    if (!VIDEO_ID.test(videoId)) return <>{photo}</>;

    /**
     * Lifted over the chips and the gradient the frame lays on top of its
     * image, so the player is the whole frame once it is running.
     */
    if (playing) {
        return (
            <iframe
                src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1`}
                title={`${title} — video`}
                loading="lazy"
                referrerPolicy="strict-origin-when-cross-origin"
                allow={PLAYER_ALLOW}
                allowFullScreen
                className="absolute inset-0 z-20 h-full w-full border-0 bg-surface-2"
            />
        );
    }

    return (
        <button
            type="button"
            onClick={() => setPlaying(true)}
            aria-label={`Play the video for ${title}`}
            className="group/video absolute inset-0 block h-full w-full cursor-pointer focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary"
        >
            {/*
                YouTube's still for this video. It comes from the image CDN
                rather than the player, so nothing here sets a cookie, and no
                referrer goes with it: which recipe is being read is not
                Google's business until the video is actually asked for.
            */}
            <Photo
                src={`https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`}
                alt=""
                loading="lazy"
                referrerPolicy="no-referrer"
                className="h-full w-full object-cover"
                fallback={photo}
            />
            <span className="pointer-events-none absolute inset-0 grid place-items-center">
                <span className="inline-flex size-14 items-center justify-center rounded-full bg-primary text-on-primary shadow-glow-primary transition-transform duration-300 ease-out group-hover/video:scale-110 group-active/video:scale-95 sm:size-16">
                    <Play className="size-6 translate-x-0.5 fill-current sm:size-7" aria-hidden="true" />
                </span>
            </span>
        </button>
    );
};
