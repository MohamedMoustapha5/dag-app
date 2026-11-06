import { Clock, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';

const COORDONNEES = [
    {
        key: 'whatsapp',
        titre: 'WhatsApp',
        valeur: '+237 6 98 57 28 93',
        href: 'https://wa.me/237698572893',
        icon: MessageCircle,
        iconBg: 'var(--dag-green-bg)',
        iconColor: 'var(--dag-green)',
    },
    {
        key: 'telephone',
        titre: 'Téléphone',
        valeur: '+237 6 98 57 28 93',
        href: 'tel:+237698572893',
        icon: Phone,
        iconBg: 'var(--dag-blue-light)',
        iconColor: 'var(--dag-blue)',
    },
    {
        key: 'email',
        titre: 'E-mail',
        valeur: 'contact@dag.cm',
        href: 'mailto:contact@dag.cm',
        icon: Mail,
        iconBg: 'var(--dag-orange-bg)',
        iconColor: 'var(--dag-orange-ink)',
    },
    {
        key: 'adresse',
        titre: 'Adresse',
        valeur: 'Akwa, Douala, Cameroun',
        href: null,
        icon: MapPin,
        iconBg: '#F1F2F6',
        iconColor: 'var(--dag-text-muted)',
    },
    {
        key: 'horaires',
        titre: 'Horaires',
        valeur: 'Lun – Ven, 8h – 18h',
        href: null,
        icon: Clock,
        iconBg: '#F1F2F6',
        iconColor: 'var(--dag-text-muted)',
    },
];

/**
 * DAG's contact details (WhatsApp, phone, e-mail, address, opening hours),
 * shown next to the public contact form and the espaces' support form.
 */
export default function CoordonneesDag() {
    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                gap: 16,
            }}
        >
            {COORDONNEES.map((info) => {
                const content = (
                    <>
                        <span
                            className="contact-info-icon"
                            style={{
                                background: info.iconBg,
                                color: info.iconColor,
                            }}
                        >
                            <info.icon size={18} />
                        </span>
                        <div>
                            <p className="contact-info-title">{info.titre}</p>
                            <p className="contact-info-value">{info.valeur}</p>
                        </div>
                    </>
                );

                return info.href ? (
                    <a
                        key={info.key}
                        href={info.href}
                        target="_blank"
                        rel="noreferrer"
                        className="card contact-info-card"
                    >
                        {content}
                    </a>
                ) : (
                    <div key={info.key} className="card contact-info-card">
                        {content}
                    </div>
                );
            })}
        </div>
    );
}
