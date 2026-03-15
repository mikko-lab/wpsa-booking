<?php
/**
 * Booking Wizard Template
 * Toteuttaa rautalankamallin mukaisesti Apple-tyylisen varausnäkymän
 *
 * @package WPSA_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wpsa-booking-wizard" role="main" aria-labelledby="wpsa-wizard-title">
    
    <!-- Skip link -->
    <a href="#wpsa-booking-form" class="wpsa-skip-link">
        <?php esc_html_e( 'Siirry varauslomakkeeseen', 'wpsa-booking' ); ?>
    </a>

    <!-- Header -->
    <header class="wpsa-header">
        <div class="wpsa-logo">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <strong><?php bloginfo( 'name' ); ?></strong>
            <?php endif; ?>
        </div>
        <nav aria-label="<?php esc_attr_e( 'Apulinkit', 'wpsa-booking' ); ?>" class="wpsa-help-nav">
            <a href="#help"><?php esc_html_e( 'Apua', 'wpsa-booking' ); ?></a>
            <button type="button" 
                    aria-label="<?php esc_attr_e( 'Saavutettavuusasetukset', 'wpsa-booking' ); ?>"
                    data-wpsa-action="open-accessibility-settings">
                <?php esc_html_e( 'Saavutettavuus', 'wpsa-booking' ); ?>
            </button>
        </nav>
    </header>

    <!-- Progress indicator -->
    <div class="wpsa-progress" 
         role="progressbar" 
         aria-valuenow="2" 
         aria-valuemin="1" 
         aria-valuemax="3"
         aria-label="<?php esc_attr_e( 'Vaihe 2/3: Valitse aika', 'wpsa-booking' ); ?>">
        <span class="wpsa-step wpsa-step--complete" aria-label="<?php esc_attr_e( 'Vaihe 1: Valmis', 'wpsa-booking' ); ?>">1</span>
        <span class="wpsa-step wpsa-step--active" aria-current="step" aria-label="<?php esc_attr_e( 'Vaihe 2: Aktiivinen', 'wpsa-booking' ); ?>">2</span>
        <span class="wpsa-step wpsa-step--incomplete" aria-label="<?php esc_attr_e( 'Vaihe 3: Keskeneräinen', 'wpsa-booking' ); ?>">3</span>
    </div>

    <!-- Main content -->
    <main class="wpsa-content" id="wpsa-booking-form">
        
        <h1 id="wpsa-wizard-title">
            <?php esc_html_e( 'Milloin keskustellaan saavutettavuudesta?', 'wpsa-booking' ); ?>
        </h1>
        
        <p>
            <?php esc_html_e( 'Valitse kalenterista päivä ja vapaa kellonaika videoneuvottelulle.', 'wpsa-booking' ); ?>
        </p>

        <!-- ARIA live region ruudunlukijoille -->
        <div id="wpsa-live-region" 
             class="wpsa-sr-only" 
             aria-live="polite" 
             aria-atomic="true">
        </div>

        <!-- Calendar widget -->
        <div class="wpsa-calendar" 
             role="application" 
             aria-label="<?php esc_attr_e( 'Kalenterivalitsin', 'wpsa-booking' ); ?>">
            
            <div class="wpsa-calendar-header">
                <h2 class="wpsa-calendar-title" id="wpsa-calendar-month">
                    <!-- Täytetään JavaScriptillä -->
                </h2>
                <div class="wpsa-calendar-nav">
                    <button type="button" 
                            id="wpsa-prev-month"
                            aria-label="<?php esc_attr_e( 'Edellinen kuukausi', 'wpsa-booking' ); ?>">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" 
                            id="wpsa-next-month"
                            aria-label="<?php esc_attr_e( 'Seuraava kuukausi', 'wpsa-booking' ); ?>">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M6 4L10 8L6 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Calendar grid - täytetään JavaScriptillä -->
            <div class="wpsa-calendar-grid" 
                 id="wpsa-calendar-grid"
                 role="grid"
                 aria-labelledby="wpsa-calendar-month">
                
                <!-- Viikonpäivät -->
                <div class="wpsa-calendar-weekday" role="columnheader">Ma</div>
                <div class="wpsa-calendar-weekday" role="columnheader">Ti</div>
                <div class="wpsa-calendar-weekday" role="columnheader">Ke</div>
                <div class="wpsa-calendar-weekday" role="columnheader">To</div>
                <div class="wpsa-calendar-weekday" role="columnheader">Pe</div>
                <div class="wpsa-calendar-weekday" role="columnheader">La</div>
                <div class="wpsa-calendar-weekday" role="columnheader">Su</div>

                <!-- Päivät generoidaan JavaScriptillä -->
            </div>

        </div>

        <!-- Time slots - ladataan kun päivä valittu -->
        <div id="wpsa-time-slots-container" 
             aria-live="polite" 
             aria-atomic="true"
             style="display: none;">
            
            <h3><?php esc_html_e( 'Valitse sopiva kellonaika', 'wpsa-booking' ); ?></h3>
            
            <div class="wpsa-time-slots" 
                 id="wpsa-time-slots"
                 role="radiogroup"
                 aria-label="<?php esc_attr_e( 'Vapaat kellonajat', 'wpsa-booking' ); ?>">
                <!-- Ladataan JavaScriptillä -->
            </div>

        </div>

        <!-- Loading state -->
        <div id="wpsa-loading-slots" 
             class="wpsa-empty-state" 
             style="display: none;"
             role="status"
             aria-live="polite">
            <span class="wpsa-loading" aria-hidden="true"></span>
            <p><?php esc_html_e( 'Ladataan vapaita aikoja...', 'wpsa-booking' ); ?></p>
        </div>

        <!-- Empty state -->
        <div id="wpsa-no-slots" 
             class="wpsa-empty-state" 
             style="display: none;"
             role="status">
            <p><?php esc_html_e( 'Ei vapaita aikoja tälle päivälle. Valitse toinen päivä.', 'wpsa-booking' ); ?></p>
        </div>

        <!-- Error state -->
        <div id="wpsa-error" 
             class="wpsa-error-message" 
             style="display: none;"
             role="alert"
             aria-live="assertive">
            <!-- Virheviesti täytetään JavaScriptillä -->
        </div>

    </main>

    <!-- Navigation -->
    <footer class="wpsa-nav">
        <button type="button" 
                class="wpsa-btn wpsa-btn-secondary"
                id="wpsa-prev-step"
                aria-label="<?php esc_attr_e( 'Edellinen vaihe', 'wpsa-booking' ); ?>">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php esc_html_e( 'Edellinen', 'wpsa-booking' ); ?>
        </button>
        
        <button type="button" 
                class="wpsa-btn wpsa-btn-primary"
                id="wpsa-next-step"
                disabled
                aria-label="<?php esc_attr_e( 'Seuraava vaihe', 'wpsa-booking' ); ?>">
            <?php esc_html_e( 'Seuraava', 'wpsa-booking' ); ?>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M6 4L10 8L6 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </footer>

</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="wpsa-booking-data">
<?php
echo json_encode(
    array(
        'services' => array(
            array(
                'id'       => 'wcag-riskikartoitus',
                'name'     => 'WCAG-riskikartoitus',
                'price'    => 490,
                'duration' => 45,
            ),
            array(
                'id'       => 'wcag-syvaauditointi',
                'name'     => 'WCAG-syväauditointi',
                'price'    => 1290,
                'duration' => 90,
            ),
        ),
        'selectedService' => 'wcag-riskikartoitus', // Oletusvalinta
    )
);
?>
</script>
