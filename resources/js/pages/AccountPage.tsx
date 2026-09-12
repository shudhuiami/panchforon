import React, { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Lock, Mail, User } from 'lucide-react';
import { accountApi } from '../api/account';
import { ApiError } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { AccountLayout } from '../features/account/AccountLayout';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';
import { Avatar } from '../components/ui/Avatar';

type FieldErrors = Record<string, string[]>;

const firstError = (errors: FieldErrors | undefined, field: string): string | undefined => errors?.[field]?.[0];

const Card: React.FC<{ title: string; description: string; children: React.ReactNode }> = ({ title, description, children }) => (
    <section className="rounded-3xl border border-line bg-surface p-6 sm:p-8">
        <h2 className="font-display text-2xl font-semibold text-ink">{title}</h2>
        <p className="mt-1 text-sm text-ink-2">{description}</p>
        <div className="mt-6">{children}</div>
    </section>
);

export const AccountPage: React.FC = () => {
    const { user, setUser, adoptToken } = useAuth();

    const [name, setName] = useState(user?.name ?? '');
    const [email, setEmail] = useState(user?.email ?? '');
    const [profileErrors, setProfileErrors] = useState<FieldErrors>();
    const [profileMessage, setProfileMessage] = useState<string | null>(null);

    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [passwordErrors, setPasswordErrors] = useState<FieldErrors>();
    const [passwordMessage, setPasswordMessage] = useState<string | null>(null);

    const profileMutation = useMutation({
        mutationFn: () => accountApi.updateProfile({ name: name.trim(), email: email.trim() }),
        onSuccess: (res) => {
            setUser(res.data);
            setProfileErrors(undefined);
            setProfileMessage('Your details are saved.');
        },
        onError: (error: ApiError) => {
            setProfileMessage(null);
            setProfileErrors(error.errors ?? { name: [error.message] });
        },
    });

    const passwordMutation = useMutation({
        mutationFn: () => accountApi.updatePassword({ current_password: currentPassword, password, password_confirmation: passwordConfirmation }),
        onSuccess: (res) => {
            adoptToken(res.token);
            setCurrentPassword('');
            setPassword('');
            setPasswordConfirmation('');
            setPasswordErrors(undefined);
            setPasswordMessage(res.message);
        },
        onError: (error: ApiError) => {
            setPasswordMessage(null);
            setPasswordErrors(error.errors ?? { current_password: [error.message] });
        },
    });

    if (!user) return null;

    const joined = user.created_at ? new Date(user.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long' }) : null;

    return (
        <AccountLayout title="Your kitchen, your details." blurb="Change how you appear to the community and keep your sign-in secure.">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-start">
                <div className="space-y-6">
                    <section className="flex items-center gap-4 rounded-3xl border border-line bg-surface p-6">
                        <Avatar name={user.name} size="lg" />
                        <div className="min-w-0">
                            <p className="truncate font-display text-xl font-semibold text-ink">{user.name}</p>
                            <p className="truncate text-sm text-ink-3">{user.email}</p>
                            {joined && <p className="mt-1 text-xs text-ink-3">Cooking here since {joined}</p>}
                        </div>
                    </section>

                    <Card title="Your details" description="Your name is what other cooks see on your recipes and reviews.">
                        {profileMessage && (
                            <Alert variant="success" onClose={() => setProfileMessage(null)} className="mb-5">
                                {profileMessage}
                            </Alert>
                        )}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                profileMutation.mutate();
                            }}
                            className="space-y-5"
                        >
                            <Input label="Name" id="account-name" value={name} onChange={(e) => setName(e.target.value)} leftIcon={<User className="size-4" />} errorMessage={firstError(profileErrors, 'name')} />
                            <Input
                                label="Email"
                                id="account-email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                leftIcon={<Mail className="size-4" />}
                                errorMessage={firstError(profileErrors, 'email')}
                                helperText="Changing this means the address has to be confirmed again."
                            />
                            <Button type="submit" isLoading={profileMutation.isPending}>
                                Save details
                            </Button>
                        </form>
                    </Card>
                </div>

                <Card title="Password" description="Changing it signs out every other device, and keeps you signed in here.">
                    {passwordMessage && (
                        <Alert variant="success" onClose={() => setPasswordMessage(null)} className="mb-5">
                            {passwordMessage}
                        </Alert>
                    )}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            passwordMutation.mutate();
                        }}
                        className="space-y-5"
                    >
                        <Input
                            label="Current password"
                            id="account-current-password"
                            type="password"
                            autoComplete="current-password"
                            value={currentPassword}
                            onChange={(e) => setCurrentPassword(e.target.value)}
                            leftIcon={<Lock className="size-4" />}
                            errorMessage={firstError(passwordErrors, 'current_password')}
                        />
                        <Input
                            label="New password"
                            id="account-new-password"
                            type="password"
                            autoComplete="new-password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            leftIcon={<Lock className="size-4" />}
                            errorMessage={firstError(passwordErrors, 'password')}
                            helperText="At least 8 characters."
                        />
                        <Input
                            label="Confirm new password"
                            id="account-confirm-password"
                            type="password"
                            autoComplete="new-password"
                            value={passwordConfirmation}
                            onChange={(e) => setPasswordConfirmation(e.target.value)}
                            leftIcon={<Lock className="size-4" />}
                        />
                        <Button type="submit" isLoading={passwordMutation.isPending}>
                            Change password
                        </Button>
                    </form>
                </Card>
            </div>
        </AccountLayout>
    );
};
