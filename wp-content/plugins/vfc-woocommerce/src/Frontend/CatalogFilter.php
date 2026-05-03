<?php
declare(strict_types=1);

namespace VFC\Woo\Frontend;

use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Woo\Services\BeneficiarioSession;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filtra el catalogo de WooCommerce excluyendo los productos que el centro del beneficiario
 * activo ha desactivado. Si no hay beneficiario activo, no se filtra (catalogo global).
 */
final class CatalogFilter
{
    private BeneficiarioSession $session;
    private EdicionRepository $ediciones;
    private CentroProductoRepository $centroProductos;

    public function __construct(
        ?BeneficiarioSession $session = null,
        ?EdicionRepository $ediciones = null,
        ?CentroProductoRepository $centroProductos = null
    ) {
        $this->session = $session ?? new BeneficiarioSession();
        $this->ediciones = $ediciones ?? new EdicionRepository();
        $this->centroProductos = $centroProductos ?? new CentroProductoRepository();
    }

    public function register(): void
    {
        add_action('pre_get_posts', [$this, 'filterCatalog']);
    }

    public function filterCatalog(\WP_Query $query): void
    {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        $postType = $query->get('post_type');
        $isProductQuery = ($postType === 'product')
            || $query->is_post_type_archive('product')
            || $query->is_tax(['product_cat', 'product_tag']);
        if (!$isProductQuery) {
            return;
        }

        $excluded = $this->excludedProductIds();
        if ($excluded === []) {
            return;
        }
        $existing = (array) $query->get('post__not_in', []);
        $query->set('post__not_in', array_values(array_unique(array_merge($existing, $excluded))));
    }

    /**
     * @return array<int, int>
     */
    private function excludedProductIds(): array
    {
        $matricula = $this->session->readMatricula();
        if ($matricula === null) {
            return [];
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        if ($edicion === null) {
            return [];
        }
        $map = $this->centroProductos->statusMapForCentro($edicion->centroId);
        $excluded = [];
        foreach ($map as $productId => $activo) {
            if (!$activo) {
                $excluded[] = (int) $productId;
            }
        }
        return $excluded;
    }
}
