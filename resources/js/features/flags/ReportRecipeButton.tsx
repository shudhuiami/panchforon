import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Flag } from 'lucide-react';
import { flagsApi } from '../../api/flags';
import { ApiError } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { Modal } from '../../components/ui/Modal';
import { Button } from '../../components/ui/Button';
import { Textarea } from '../../components/ui/Textarea';
import { Alert } from '../../components/ui/Alert';
import { fieldClass, fieldLabelClass } from '../../components/ui/Input';

interface ReportRecipeButtonProps {
    recipeId: number;
    recipeTitle: string;
    className?: string;
}

/**
 * Reporting a recipe. Signed out, it sends the cook to sign in first, the
 * same as saving and rating do.
 */
export const ReportRecipeButton: React.FC<ReportRecipeButtonProps> = ({ recipeId, recipeTitle, className = '' }) => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [isOpen, setIsOpen] = useState(false);
    const [reason, setReason] = useState('');
    const [note, setNote] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [sentMessage, setSentMessage] = useState<string | null>(null);

    const { data: reasons } = useQuery({
        queryKey: ['flag-reasons'],
        queryFn: () => flagsApi.reasons(),
        select: (res) => res.data,
        staleTime: Infinity,
        enabled: isOpen,
    });

    const reportMutation = useMutation({
        mutationFn: () => flagsApi.report(recipeId, { reason, note: note.trim() || undefined }),
        onSuccess: (res) => setSentMessage(res.message),
        onError: (err: ApiError) => setError(err.message || 'That report could not be sent.'),
    });

    const open = () => {
        if (!user) {
            navigate('/login', { state: { from: location } });
            return;
        }
        setReason('');
        setNote('');
        setError(null);
        setSentMessage(null);
        setIsOpen(true);
    };

    return (
        <>
            <button
                type="button"
                onClick={open}
                className={`inline-flex cursor-pointer items-center gap-1.5 text-sm text-ink-3 transition-colors hover:text-hot focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${className}`}
            >
                <Flag className="size-3.5" aria-hidden="true" />
                Report this recipe
            </button>

            <Modal isOpen={isOpen} onClose={() => setIsOpen(false)} title="Report this recipe" maxWidth="sm">
                {sentMessage ? (
                    <div className="space-y-5">
                        <Alert variant="success">{sentMessage}</Alert>
                        <Button variant="secondary" onClick={() => setIsOpen(false)} className="w-full">
                            Close
                        </Button>
                    </div>
                ) : (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!reason) {
                                setError('Pick a reason so a moderator knows what to look at.');
                                return;
                            }
                            setError(null);
                            reportMutation.mutate();
                        }}
                        className="space-y-5"
                    >
                        <p className="truncate text-sm text-ink-2">
                            Something wrong with <span className="font-semibold text-ink">{recipeTitle}</span>?
                        </p>

                        <div>
                            <label htmlFor="report-reason" className={fieldLabelClass}>
                                Reason
                            </label>
                            <select
                                id="report-reason"
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                                className={`${fieldClass(false)} min-h-12 cursor-pointer px-4 py-3 text-base`}
                            >
                                <option value="">Choose one…</option>
                                {(reasons ?? []).map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <Textarea
                            id="report-note"
                            label="Anything to add"
                            helperText="Optional, but it helps a moderator decide quickly."
                            rows={3}
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            maxLength={1000}
                        />

                        {error && <Alert variant="error">{error}</Alert>}

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="ghost" onClick={() => setIsOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" isLoading={reportMutation.isPending}>
                                Send report
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>
        </>
    );
};
