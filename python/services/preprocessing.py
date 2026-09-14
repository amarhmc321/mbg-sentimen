import re

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

stemmer = StemmerFactory().create_stemmer()
stopword = StopWordRemoverFactory().create_stop_word_remover()


# ==========================================
# CASE FOLDING
# ==========================================
def case_folding(text):
    return text.lower()


# ==========================================
# CLEANING
# ==========================================
def cleaning(text):

    # Hapus URL
    text = re.sub(r'https?://\S+', '', text)

    # Hapus www
    text = re.sub(r'www\.\S+', '', text)

    # Hapus Mention
    text = re.sub(r'@[A-Za-z0-9_]+', '', text)

    # Hapus Hashtag
    text = re.sub(r'#[A-Za-z0-9_]+', '', text)

    # Hapus karakter selain huruf
    text = re.sub(r'[^a-zA-Z\s]', '', text)

    # Hilangkan spasi berlebih
    text = re.sub(r'\s+', ' ', text)

    return text.strip()


# ==========================================
# NORMALISASI HURUF BERULANG
# ==========================================
def normalize_repeated_characters(text):

    return re.sub(r'(.)\1{2,}', r'\1', text)


# ==========================================
# TOKENIZING
# ==========================================
def tokenizing(text):

    return text.split()


# ==========================================
# STOPWORD REMOVAL
# ==========================================
def stopword_removal(text):

    return stopword.remove(text)


# ==========================================
# STEMMING
# ==========================================
def stemming(text):

    return stemmer.stem(text)