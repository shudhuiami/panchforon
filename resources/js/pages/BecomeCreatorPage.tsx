import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowRight, BadgeCheck, ChefHat, Clock, LayoutDashboard, Sparkles, Video, type LucideIcon } from 'lucide-react';
import { creatorsApi } from '../api/creators';
import { ApiError } from '../api/client';
import { CreatorApplication } from '../types/api';
import { useAuth } from '../context/AuthContext';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Alert } from '../components/ui/Alert';
import { Button, ButtonLink, buttonClassName } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Textarea } from '../components/ui/Textarea';

const PITCH_LIMIT = 2000;

type FieldErrors = Record<string, string[]>;

const firstError = (errors: FieldErrors | undefined, field: string): string | undefined => errors?.[field]?.[0];

const formatDate = (value?: string | null): string | null => (value ? new Date(value).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' }) : null);

interface Perk {
    icon: LucideIcon;
    title: string;
    text: string;
}

/** What the role actually buys, said plainly rather than sold. */
const PERKS: Perk[] = [
    {
        icon: LayoutDashboard,
        title: 'A studio of your own',
        text: 'Every dish you have posted in one place, yours to edit, tidy up or take down whenever you like.',
    },
    {
        icon: Sparkles,
        title: 'Publish on save',
        text: 'No queue and no waiting. What you write goes onto the site the moment you save it.',
    },
    {
        icon: Video,
        title: 'Your channel on your recipes',
        text: 'Tell us where you film and each of your recipes can carry a video from your channel.',
    },
];

/** The frame every state of this page sits in, so the card never jumps about. */
const Panel: React.FC<{ children: React.ReactNode }> = ({ children }) => (
    <section className="mt-10 rounded-3xl border border-line bg-surface p-6 sm:mt-12 sm:p-8">{children}</section>
);

const PanelHeading: React.FC<{ icon: LucideIcon; title: string; text: string }> = ({ icon: Icon, title, text }) => (
    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-5">
        <span className="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <Icon className="size-6" aria-hidden="true" />
        </span>
        <div className="min-w-0">
            <h2 className="font-display text-2xl font-semibold tracking-tight text-ink text-balance">{title}</h2>
            <p className="mt-2 text-sm text-ink-2 sm:text-base">{text}</p>
        </div>
    </header>
);

/** What the applicant told us, shown back to them so they know what is on file. */
const PitchOnFile: React.FC<{ application: CreatorApplication }> = ({ application }) => (
    <div className="mt-6 rounded-2xl border border-line bg-surface-2 p-4 sm:p-5">
        <p className="text-xs font-semibold tracking-[0.14em] text-ink-3 uppercase">What you sent</p>
        <p className="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-2">{application.pitch}</p>
        {application.youtube_channel_url && (
            <p className="mt-3 truncate text-sm text-ink-3">
                Channel:{' '}
                <a href={application.youtube_channel_url} rel="noreferrer noopener" target="_blank" className="underline decoration-line-strong underline-offset-4 hover:text-ink">
                    {application.youtube_channel_url}
                </a>
            </p>
        )}
    </div>
);

interface ApplicationFormProps {
    /** A declined pitch comes back in the box, so a second attempt starts from the first. */
    previous: CreatorApplication | null;
    submitLabel: string;
}

const ApplicationForm: React.FC<ApplicationFormProps> = ({ previous, submitLabel }) => {
    const queryClient = useQueryClient();
    const [pitch, setPitch] = useState(previous?.pitch ?? '');
    const [channelUrl, setChannelUrl] = useState(previous?.youtube_channel_url ?? '');
    const [errors, setErrors] = useState<FieldErrors>();
    const [failure, setFailure] = useState<string | null>(null);

    const applyMutation = useMutation({
        mutationFn: () => creatorsApi.apply({ pitch: pitch.trim(), youtube_channel_url: channelUrl.trim() || null }),
        onSuccess: () => {
            setErrors(undefined);
            setFailure(null);
            queryClient.invalidateQueries({ queryKey: ['creator-application'] });
        },
        onError: (error: ApiError) => {
            setErrors(error.errors);
            setFailure(error.errors ? null : error.message);
        },
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                applyMutation.mutate();
            }}
            className="mt-6 space-y-5"
        >
            {failure && (
                <Alert variant="error" onClose={() => setFailure(null)}>
                    {failure}
                </Alert>
            )}

            <Textarea
                label="What do you cook?"
                id="creator-pitch"
                rows={7}
                maxLength={PITCH_LIMIT}
                value={pitch}
                onChange={(e) => setPitch(e.target.value)}
                placeholder="The food you know best, where you learned it, and what you would post here first."
                errorMessage={firstError(errors, 'pitch')}
                helperText={`${pitch.length} of ${PITCH_LIMIT} characters. A short, honest paragraph is plenty.`}
            />

            <Input
                label="YouTube channel (optional)"
                id="creator-channel"
                type="url"
                inputMode="url"
                value={channelUrl}
                onChange={(e) => setChannelUrl(e.target.value)}
                placeholder="https://www.youtube.com/@yourchannel"
                leftIcon={<Video className="size-4" />}
                errorMessage={firstError(errors, 'youtube_channel_url')}
                helperText="Only if you film. We use it to put a video on your recipes."
            />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button type="submit" size="lg" isLoading={applyMutation.isPending} disabled={pitch.trim().length === 0}>
                    {submitLabel}
                </Button>
                <p className="text-sm text-ink-3">We read every one. You will hear back on this page.</p>
            </div>
        </form>
    );
};

/**
 * The storefront side of asking to be trusted with the catalogue.
 *
 * Four things can be true when a cook lands here, and the page says which:
 * they have never applied, they are waiting on a decision, they were declined
 * and may try again, or they are a creator already and want the studio.
 */
export const BecomeCreatorPage: React.FC = () => {
    const { user } = useAuth();
    const [reapplying, setReapplying] = useState(false);

    const alreadyACreator = user?.role === 'creator' || user?.role === 'admin';

    const { data, isPending } = useQuery({
        queryKey: ['creator-application'],
        queryFn: creatorsApi.myApplication,
        enabled: !!user && !alreadyACreator,
    });

    if (!user) return null;

    const application = data?.data ?? null;

    const body = (): React.ReactNode => {
        if (alreadyACreator) {
            return (
                <Panel>
                    <PanelHeading
                        icon={BadgeCheck}
                        title="You are already a creator."
                        text="Nothing to apply for: your recipes publish the moment you save them, and the studio is where you keep them."
                    />
                    <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                        <a href="/studio" className={buttonClassName({ size: 'lg' })}>
                            Open your studio
                            <ArrowRight className="size-4" aria-hidden="true" />
                        </a>
                        <ButtonLink to="/recipes/create" variant="secondary" size="lg">
                            Post a recipe
                        </ButtonLink>
                    </div>
                </Panel>
            );
        }

        if (isPending) {
            return <LoadingSpinner message="Checking where your application has got to…" />;
        }

        if (application?.status === 'pending') {
            return (
                <Panel>
                    <PanelHeading
                        icon={Clock}
                        title="We have your application."
                        text="It is with us now. Applications are read by hand, so give it a few days — the answer turns up right here."
                    />
                    <PitchOnFile application={application} />
                    {formatDate(application.created_at) && <p className="mt-4 text-sm text-ink-3">Sent on {formatDate(application.created_at)}.</p>}
                </Panel>
            );
        }

        if (application?.status === 'declined') {
            return (
                <Panel>
                    <PanelHeading
                        icon={ChefHat}
                        title="Not this time."
                        text="A decline is not a door closing. Take what is below on board and send us another one whenever you are ready."
                    />
                    {application.review_note && (
                        <Alert variant="warning" title="What we said" className="mt-6">
                            {application.review_note}
                        </Alert>
                    )}
                    {formatDate(application.reviewed_at) && <p className="mt-4 text-sm text-ink-3">Decided on {formatDate(application.reviewed_at)}.</p>}
                    {reapplying ? (
                        <ApplicationForm previous={application} submitLabel="Send it again" />
                    ) : (
                        <Button size="lg" className="mt-6" onClick={() => setReapplying(true)}>
                            Apply again
                        </Button>
                    )}
                </Panel>
            );
        }

        return (
            <Panel>
                <PanelHeading icon={ChefHat} title="Tell us what you cook." text="One paragraph is enough. We are reading for the cooking, not the writing." />
                <ApplicationForm previous={null} submitLabel="Send application" />
            </Panel>
        );
    };

    return (
        <div className="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="max-w-2xl animate-slide-up">
                <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Become a creator</p>
                <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">Cook for more than your own kitchen.</h1>
                <p className="mt-4 text-base text-ink-2 sm:text-lg">
                    Recipes here come from cooks we have read and trusted with the catalogue. Tell us what you cook and we will take a look.
                </p>
            </header>

            <ul className="mt-10 grid gap-4 sm:grid-cols-3">
                {PERKS.map(({ icon: Icon, title, text }) => (
                    <li key={title} className="rounded-3xl border border-line bg-surface p-5">
                        <span className="inline-flex size-10 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                            <Icon className="size-5" aria-hidden="true" />
                        </span>
                        <h2 className="mt-4 font-display text-lg font-semibold text-ink">{title}</h2>
                        <p className="mt-1.5 text-sm leading-relaxed text-ink-2">{text}</p>
                    </li>
                ))}
            </ul>

            {body()}
        </div>
    );
};
