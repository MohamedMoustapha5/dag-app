import { Mail, MessageCircle, Phone, X } from 'lucide-react';
import { useState } from 'react';

const CONTACT_CHANNELS = [
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        href: 'https://wa.me/237699000000',
        icon: MessageCircle,
        iconBg: 'var(--dag-green-bg)',
        iconColor: 'var(--dag-green)',
    },
    {
        key: 'phone',
        label: '+237 6 99 00 00 00',
        href: 'tel:+237699000000',
        icon: Phone,
        iconBg: 'var(--dag-blue-light)',
        iconColor: 'var(--dag-blue)',
    },
    {
        key: 'email',
        label: 'contact@dag.cm',
        href: 'mailto:contact@dag.cm',
        icon: Mail,
        iconBg: 'var(--dag-orange-bg)',
        iconColor: 'var(--dag-orange-ink)',
    },
];

export default function ContactFab() {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div className={`contact-fab${isOpen ? ' open' : ''}`}>
            <div className="contact-fab-panel">
                <p className="contact-fab-panel-title">Contactez DAG</p>
                <p className="contact-fab-panel-sub">
                    On vous répond rapidement
                </p>
                {CONTACT_CHANNELS.map((channel) => (
                    <a
                        key={channel.key}
                        href={channel.href}
                        className="contact-fab-row"
                        target="_blank"
                        rel="noreferrer"
                    >
                        <span
                            className="contact-fab-icon"
                            style={{
                                background: channel.iconBg,
                                color: channel.iconColor,
                            }}
                        >
                            <channel.icon size={16} />
                        </span>
                        {channel.label}
                    </a>
                ))}
            </div>

            <button
                type="button"
                className="contact-fab-btn"
                aria-label={
                    isOpen
                        ? 'Fermer le contact rapide'
                        : 'Ouvrir le contact rapide'
                }
                aria-expanded={isOpen}
                onClick={() => setIsOpen((open) => !open)}
            >
                <MessageCircle className="icon-chat" size={22} />
                <X className="icon-close" size={20} />
            </button>
        </div>
    );
}
