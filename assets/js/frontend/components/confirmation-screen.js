export class ConfirmationScreen {
    constructor(container) {
        this.container = container;
    }

    render(bookingData) {
        this.container.innerHTML = '';

        const card = document.createElement('div');
        card.className = 'silao-confirmation-card';

        const icon = document.createElement('div');
        icon.style.fontSize = '48px';
        icon.textContent = '✅';
        card.appendChild(icon);

        const heading = document.createElement('h2');
        heading.textContent = 'Réservation Confirmée !';
        card.appendChild(heading);

        const p = document.createElement('p');
        p.textContent = 'Votre demande a bien été enregistrée. Voici votre numéro de référence officiel :';
        card.appendChild(p);

        // Display server-issued reference verbatim
        const refBadge = document.createElement('div');
        refBadge.className = 'silao-ref-badge';
        refBadge.textContent = bookingData.reference;
        card.appendChild(refBadge);

        if (bookingData.quote && bookingData.quote.formatted_total) {
            const priceP = document.createElement('p');
            priceP.style.fontSize = '16px';
            priceP.textContent = `Montant total : ${bookingData.quote.formatted_total}`;
            card.appendChild(priceP);
        }

        this.container.appendChild(card);
    }
}