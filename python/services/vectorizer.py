from sklearn.feature_extraction.text import TfidfVectorizer


def generate_tfidf(documents):

    vectorizer = TfidfVectorizer()

    matrix = vectorizer.fit_transform(documents)

    feature_names = vectorizer.get_feature_names_out()

    return {

        "terms": feature_names.tolist(),

        "matrix": matrix.toarray().tolist()

    }