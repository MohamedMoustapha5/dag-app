import { useEffect, useState } from 'react';

const STORAGE_KEY = 'dag-cookie-consent';

export default function CookieBanner() {
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        try {
            if (!localStorage.getItem(STORAGE_KEY)) {
                setIsOpen(true);
            }
        } catch {
            setIsOpen(true);
        }
    }, []);

    const respond = (consent: 'accepted' | 'declined') => {
        try {
            localStorage.setItem(STORAGE_KEY, consent);
        } catch {
            // Stockage indisponible (navigation privée) : on ferme simplement le bandeau.
        }
        setIsOpen(false);
    };

    return (
        <div
            className={`cookie-banner${isOpen ? ' open' : ''}`}
            role="dialog"
            aria-live="polite"
        >
            <span className="cookie-icon" aria-hidden="true">
                🍪
            </span>
            <div style={{ flex: 1, minWidth: 180 }}>
                <p
                    style={{
                        fontSize: 13.5,
                        fontWeight: 600,
                        color: 'var(--dag-navy)',
                    }}
                >
                    Respect de votre vie privée
                </p>
                <p
                    className="muted"
                    style={{ fontSize: 12.5, marginTop: 4, lineHeight: 1.6 }}
                >
                    Nous utilisons des cookies pour améliorer votre expérience
                    sur le site DAG.
                </p>
                <div className="cookie-actions">
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => respond('accepted')}
                    >
                        Accepter
                    </button>
                    <button
                        type="button"
                        className="btn-ghost"
                        onClick={() => respond('declined')}
                    >
                        Refuser
                    </button>
                </div>
            </div>
        </div>
    );
}
