<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumMnemonic;
use BitWasp\Buffertools\Buffer;

class ElectrumKeyFactory
{

    /**
     * Pass a secret exponent (integer)
     * @param integer|string $secret
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromSecretExponent($secret, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        return new ElectrumKey(
            $ecAdapter,
            PrivateKeyFactory::fromInt($secret, false, $ecAdapter)
        );
    }

    /**
     * Generate a master private key given a
     * @param Buffer $seed
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function generateMasterKey(Buffer $seed, EcAdapterInterface $ecAdapter = null)
    {
        // Really weird, did electrum actually hash hex string seeds?
        $seed = $oldseed = $seed->getHex();

        // Perform sha256 hash 5 times per iteration
        for ($i = 0; $i < 5*20000; $i++) {
            // Hash should return binary data
            $seed = hash('sha256', $seed . $oldseed, true);
        }

        // Convert binary data to hex.
        $str = new Buffer($seed);

        return self::fromSecretExponent(
            $str->getInt(),
            $ecAdapter ?: Bitcoin::getEcAdapter()
        );
    }

    /**
     * Provide an electrum mnemonic and derive the master key
     *
     * @param $mnemonic
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromMnemonic($mnemonic, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $mnemonicConverter = new ElectrumMnemonic($ecAdapter);
        $entropy = $mnemonicConverter->mnemonicToEntropy($mnemonic);
        return self::generateMasterKey($entropy, $ecAdapter);
    }

    /**
     * Takes a key which is assumed to be either the master private key or master public key.
     *
     * @param KeyInterface $key
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromKey(KeyInterface $key, EcAdapterInterface $ecAdapter = null)
    {
        return new ElectrumKey(
            $ecAdapter ?: Bitcoin::getEcAdapter(),
            $key
        );
    }
}